<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Contracts\WhatsApp\MessageBuildServiceInterface;
use App\DTOs\AI\AiRequestDTO;
use App\DTOs\WhatsApp\ConversationContextDTO;
use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use Illuminate\Support\Facades\Log;

final class MessageBuildService implements MessageBuildServiceInterface
{
    /**
     * Build a complete AI request with system prompt, user message and context
     */
    public function buildAiRequest(
        WhatsAppAccountMetadataDTO $accountMetadata,
        ConversationContextDTO $conversationContext,
        string $userMessage
    ): AiRequestDTO {
        Log::debug('[MESSAGE_BUILD] Building AI request', [
            'session_id' => $accountMetadata->sessionId,
            'conversation_id' => $conversationContext->conversationId,
            'message_length' => strlen($userMessage),
            'has_context' => $conversationContext->hasRecentMessages(),
        ]);

        $systemPrompt = $this->buildSystemPrompt($accountMetadata, $conversationContext);
        $messageContext = $this->prepareMessageContext($conversationContext);

        $aiRequest = new AiRequestDTO(
            systemPrompt: $systemPrompt,
            userMessage: $userMessage,
            config: $this->buildAiConfig($accountMetadata),
            context: $messageContext
        );

        Log::info('[MESSAGE_BUILD] AI request built successfully', [
            'system_prompt_length' => strlen($systemPrompt),
            'context_messages' => count($messageContext),
            'ai_model_id' => $accountMetadata->aiModelId,
        ]);

        return $aiRequest;
    }

    /**
     * Build system prompt with contextual information
     */
    public function buildSystemPrompt(
        WhatsAppAccountMetadataDTO $accountMetadata,
        ConversationContextDTO $conversationContext
    ): string {
        $basePrompt = $accountMetadata->getEffectivePrompt();

        $systemPrompt = $basePrompt;

        // Add contextual information if available
        if ($conversationContext->contextualInformation) {
            $systemPrompt .= "\n\nInformations contextuelles importantes :\n";
            $systemPrompt .= $conversationContext->contextualInformation;
        }

        // Add conversation guidelines
        $systemPrompt .= "\n\nDirectives de conversation :";
        $systemPrompt .= "\n- Réponds en français de manière naturelle et conversationnelle";
        $systemPrompt .= "\n- Reste concis et pertinent";
        $systemPrompt .= "\n- Utilise un ton professionnel mais chaleureux";

        // RÈGLES ANTI-HALLUCINATION STRICTES - GÉNÉRALES
        $systemPrompt .= "\n\n⚠️ RÈGLES CRITIQUES - INTERDICTION ABSOLUE D'INVENTER :";
        $systemPrompt .= "\n- ❌ JAMAIS inventer d'informations que tu ne connais pas avec certitude";
        $systemPrompt .= "\n- ❌ JAMAIS donner de données factuelles non vérifiées (dates, prix, coordonnées, etc.)";
        $systemPrompt .= "\n- ❌ JAMAIS faire semblant de connaître des détails spécifiques si tu n'en es pas sûr";
        $systemPrompt .= "\n- ✅ Si on te pose une question dont tu ne connais pas la réponse : dire 'Je reviens vers vous dans un instant avec cette information'";
        $systemPrompt .= "\n- ✅ Être honnête sur tes limites plutôt que d'inventer";
        $systemPrompt .= "\n- ✅ Si tu doutes d'une information, demander plutôt confirmation ou dire que tu vérifies";

        // Add chat context if available
        if ($conversationContext->hasRecentMessages()) {
            $systemPrompt .= "\n\nContexte de la conversation précédente :\n";
            $systemPrompt .= $conversationContext->getFormattedHistory();
        }

        Log::debug('[MESSAGE_BUILD] System prompt built', [
            'prompt_length' => strlen($systemPrompt),
            'has_contextual_info' => ! empty($conversationContext->contextualInformation),
            'has_conversation_history' => $conversationContext->hasRecentMessages(),
        ]);

        return $systemPrompt;
    }

    /**
     * Prepare message context for AI processing
     */
    public function prepareMessageContext(ConversationContextDTO $conversationContext): array
    {
        $context = [
            'conversation_id' => $conversationContext->conversationId,
            'chat_id' => $conversationContext->chatId,
            'contact_phone' => $conversationContext->contactPhone,
            'is_group' => $conversationContext->isGroup,
            'has_history' => $conversationContext->hasRecentMessages(),
        ];

        // Add recent messages for AI context
        if ($conversationContext->hasRecentMessages()) {
            $context['recent_messages'] = $conversationContext->recentMessages;
            $context['message_count'] = count($conversationContext->recentMessages);
        }

        // Add metadata if available
        if (! empty($conversationContext->metadata)) {
            $context['metadata'] = $conversationContext->metadata;
        }

        Log::debug('[MESSAGE_BUILD] Message context prepared', [
            'context_keys' => array_keys($context),
            'recent_messages_count' => $context['message_count'] ?? 0,
        ]);

        return $context;
    }

    /**
     * Build AI configuration based on account settings
     */
    private function buildAiConfig(WhatsAppAccountMetadataDTO $accountMetadata): array
    {
        $config = [
            'model_id' => $accountMetadata->getEffectiveAiModelId(),
            'response_time' => $accountMetadata->getEffectiveResponseTime(),
            'session_id' => $accountMetadata->sessionId,
            'account_id' => $accountMetadata->accountId,
        ];

        // Add account-specific settings
        if (! empty($accountMetadata->settings)) {
            $config['account_settings'] = $accountMetadata->settings;
        }

        Log::debug('[MESSAGE_BUILD] AI config built', [
            'model_id' => $config['model_id'],
            'response_time' => $config['response_time'],
            'has_custom_settings' => ! empty($accountMetadata->settings),
        ]);

        return $config;
    }

    /**
     * Validate message content for AI processing
     */
    public function validateMessageContent(string $message): bool
    {
        // Basic validation rules
        $trimmedMessage = trim($message);

        if (empty($trimmedMessage)) {
            Log::warning('[MESSAGE_BUILD] Empty message content');

            return false;
        }

        if (strlen($trimmedMessage) > 4000) {
            Log::warning('[MESSAGE_BUILD] Message too long', [
                'length' => strlen($trimmedMessage),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Extract message intent for better AI processing
     */
    public function extractMessageIntent(string $message): array
    {
        $message = strtolower(trim($message));
        $intent = [
            'type' => 'general',
            'confidence' => 0.5,
            'keywords' => [],
        ];

        // Question detection
        if (str_contains($message, '?') ||
            str_starts_with($message, 'comment') ||
            str_starts_with($message, 'pourquoi') ||
            str_starts_with($message, 'quand') ||
            str_starts_with($message, 'où')) {
            $intent['type'] = 'question';
            $intent['confidence'] = 0.8;
        }

        // Greeting detection
        if (preg_match('/^(bonjour|salut|hello|bonsoir|hey)/i', $message)) {
            $intent['type'] = 'greeting';
            $intent['confidence'] = 0.9;
        }

        // Request detection
        if (str_contains($message, 'peux-tu') ||
            str_contains($message, 'pouvez-vous') ||
            str_contains($message, 'aide')) {
            $intent['type'] = 'request';
            $intent['confidence'] = 0.7;
        }

        Log::debug('[MESSAGE_BUILD] Message intent extracted', [
            'intent_type' => $intent['type'],
            'confidence' => $intent['confidence'],
            'message_preview' => substr($message, 0, 50),
        ]);

        return $intent;
    }

    /**
     * Format message for optimal AI processing
     */
    public function formatMessageForAI(string $message): string
    {
        // Basic cleanup
        $formatted = trim($message);

        // Remove excessive whitespace
        $formatted = preg_replace('/\s+/', ' ', $formatted);

        // Remove common noise characters
        $formatted = str_replace(['📱', '💬', '🤖'], '', $formatted);

        return $formatted;
    }
}
