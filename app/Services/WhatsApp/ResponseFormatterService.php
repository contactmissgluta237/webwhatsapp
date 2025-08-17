<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Contracts\WhatsApp\ResponseFormatterServiceInterface;
use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Enums\MessageDirection;
use App\Enums\MessageType;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Exception;

final class ResponseFormatterService implements ResponseFormatterServiceInterface
{
    /**
     * Store AI response as outgoing message and format final response
     */
    public function formatAndStoreResponse(
        WhatsAppConversation $conversation,
        WhatsAppAIResponseDTO $aiResponse,
        WhatsAppAccountMetadataDTO $accountMetadata
    ): WhatsAppMessageResponseDTO {
        Log::info('[RESPONSE_FORMATTER] Formatting and storing AI response', [
            'conversation_id' => $conversation->id,
            'session_id' => $accountMetadata->sessionId,
            'response_length' => $aiResponse->getResponseLength(),
            'model_used' => $aiResponse->model,
        ]);

        try {
            // Store the outgoing message
            $outgoingMessage = $this->storeOutgoingMessage($conversation, $aiResponse);

            // Calculate timing delays (don't apply them, just calculate)
            $timingData = $this->calculateResponseTimings($accountMetadata, $aiResponse);

            // Create successful response with timing data
            $response = WhatsAppMessageResponseDTO::success(
                $aiResponse->response,
                $aiResponse,
                $timingData['wait_time'],
                $timingData['typing_duration']
            );

            Log::info('[RESPONSE_FORMATTER] Response formatted and stored successfully', [
                'message_id' => $outgoingMessage->id,
                'conversation_id' => $conversation->id,
                'session_id' => $accountMetadata->sessionId,
            ]);

            return $response;

        } catch (Exception $e) {
            Log::error('[RESPONSE_FORMATTER] Error formatting response', [
                'conversation_id' => $conversation->id,
                'session_id' => $accountMetadata->sessionId,
                'error' => $e->getMessage(),
            ]);

            return WhatsAppMessageResponseDTO::error("Erreur formatage réponse: {$e->getMessage()}");
        }
    }

    /**
     * Calculate response timings (wait time + typing duration)
     * Does NOT apply delays - just calculates them for NodeJS/Livewire
     */
    private function calculateResponseTimings(
        WhatsAppAccountMetadataDTO $accountMetadata,
        WhatsAppAIResponseDTO $aiResponse
    ): array {
        // Calculate wait time before responding
        $waitTimeSeconds = $accountMetadata->getResponseTimeDelay();
        
        // Calculate typing duration based on response length
        $responseLength = strlen($aiResponse->response);
        $typingDurationSeconds = $this->calculateTypingDuration($responseLength);

        Log::debug('[RESPONSE_FORMATTER] Calculated response timings', [
            'session_id' => $accountMetadata->sessionId,
            'response_time_setting' => $accountMetadata->getEffectiveResponseTime(),
            'wait_time_seconds' => $waitTimeSeconds,
            'typing_duration_seconds' => $typingDurationSeconds,
            'response_length' => $responseLength,
        ]);

        return [
            'wait_time' => $waitTimeSeconds,
            'typing_duration' => $typingDurationSeconds,
        ];
    }

    /**
     * Calculate typing duration based on message length
     * Simulates realistic human typing speed with variation
     */
    private function calculateTypingDuration(int $messageLength): int
    {
        $baseTypingSpeed = 30;
        $randomValue = Arr::random(range(70, 130));
        $variation = ($randomValue - 100) / 100;
        $actualTypingSpeed = $baseTypingSpeed * (1 + $variation);
        
        $typingDuration = (int) ceil($messageLength / $actualTypingSpeed);
        
        return max(2, $typingDuration);
    }

    /**
     * Format response for webhook delivery
     */
    public function formatWebhookResponse(WhatsAppMessageResponseDTO $response): array
    {
        $webhookData = $response->toWebhookResponse();

        Log::debug('[RESPONSE_FORMATTER] Webhook response formatted', [
            'success' => $webhookData['success'],
            'processed' => $webhookData['processed'],
            'has_response_message' => !empty($webhookData['response_message']),
        ]);

        return $webhookData;
    }

    /**
     * Store outgoing AI message in database
     */
    public function storeOutgoingMessage(
        WhatsAppConversation $conversation,
        WhatsAppAIResponseDTO $aiResponse
    ): WhatsAppMessage {
        Log::debug('[RESPONSE_FORMATTER] Storing outgoing message', [
            'conversation_id' => $conversation->id,
            'response_length' => $aiResponse->getResponseLength(),
            'model' => $aiResponse->model,
        ]);

        $message = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => MessageDirection::OUTBOUND(),
            'content' => $aiResponse->response,
            'message_type' => MessageType::TEXT(),
            'is_ai_generated' => true,
            'ai_model_used' => $aiResponse->model,
            'ai_confidence' => $aiResponse->confidence,
        ]);

        Log::info('[RESPONSE_FORMATTER] Outgoing message stored successfully', [
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'ai_model' => $aiResponse->model,
            'confidence' => $aiResponse->confidence,
        ]);

        return $message;
    }

    /**
     * Build metadata for outgoing message
     */
    private function buildOutgoingMessageMetadata(WhatsAppAIResponseDTO $aiResponse): array
    {
        return [
            'ai_generated' => true,
            'ai_model' => $aiResponse->model,
            'ai_confidence' => $aiResponse->confidence,
            'tokens_used' => $aiResponse->tokensUsed,
            'cost' => $aiResponse->cost,
            'response_length' => $aiResponse->getResponseLength(),
            'high_confidence' => $aiResponse->isHighConfidence(),
            'generated_at' => now()->toISOString(),
            'metadata' => $aiResponse->metadata,
        ];
    }

    /**
     * Validate response before storing
     */
    public function validateResponse(WhatsAppAIResponseDTO $aiResponse): bool
    {
        if (!$aiResponse->hasValidResponse()) {
            Log::warning('[RESPONSE_FORMATTER] Invalid AI response - empty content');
            return false;
        }

        if ($aiResponse->getResponseLength() > 4096) {
            Log::warning('[RESPONSE_FORMATTER] AI response too long', [
                'length' => $aiResponse->getResponseLength(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Apply response filters (profanity, safety, etc.)
     */
    public function applyResponseFilters(string $response): string
    {
        // Basic content filtering
        $filtered = trim($response);

        // Remove potential harmful patterns
        $filtered = preg_replace('/https?:\/\/[^\s]+/i', '[lien supprimé]', $filtered);

        // Remove phone numbers patterns
        $filtered = preg_replace('/\b\d{10,}\b/', '[numéro supprimé]', $filtered);

        Log::debug('[RESPONSE_FORMATTER] Response filters applied', [
            'original_length' => strlen($response),
            'filtered_length' => strlen($filtered),
            'filters_applied' => $response !== $filtered,
        ]);

        return $filtered;
    }

    /**
     * Format response for different output types
     */
    public function formatForOutput(WhatsAppAIResponseDTO $aiResponse, string $outputType = 'whatsapp'): string
    {
        $content = $aiResponse->response;

        switch ($outputType) {
            case 'whatsapp':
                // WhatsApp specific formatting
                $content = str_replace('**', '*', $content); // Bold formatting
                $content = str_replace('__', '_', $content); // Italic formatting
                break;

            case 'plain':
                // Remove all formatting
                $content = strip_tags($content);
                $content = preg_replace('/[*_`~]/', '', $content);
                break;

            case 'html':
                // Convert markdown-like to HTML
                $content = str_replace('**', '<strong>', $content);
                $content = str_replace('*', '</strong>', $content);
                break;
        }

        Log::debug('[RESPONSE_FORMATTER] Response formatted for output', [
            'output_type' => $outputType,
            'original_length' => strlen($aiResponse->response),
            'formatted_length' => strlen($content),
        ]);

        return $content;
    }

    /**
     * Get response statistics
     */
    public function getResponseStats(WhatsAppConversation $conversation): array
    {
        $stats = [
            'total_ai_responses' => $conversation->messages()
                ->where('direction', MessageDirection::OUTBOUND())
                ->where('is_ai_generated', true)
                ->count(),
            'average_response_length' => $conversation->messages()
                ->where('direction', MessageDirection::OUTBOUND())
                ->where('is_ai_generated', true)
                ->selectRaw('AVG(LENGTH(content))')
                ->value('AVG(LENGTH(content))') ?? 0,
            'models_used' => $conversation->messages()
                ->where('is_ai_generated', true)
                ->distinct('ai_model_used')
                ->pluck('ai_model_used')
                ->filter()
                ->toArray(),
        ];

        Log::debug('[RESPONSE_FORMATTER] Response statistics generated', [
            'conversation_id' => $conversation->id,
            'total_ai_responses' => $stats['total_ai_responses'],
            'models_count' => count($stats['models_used']),
        ]);

        return $stats;
    }
}
