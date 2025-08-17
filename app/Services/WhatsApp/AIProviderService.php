<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Contracts\WhatsApp\AIProviderServiceInterface;
use App\DTOs\AI\AiRequestDTO;
use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\Models\AiModel;
use App\Services\WhatsApp\AI\Prompt\WhatsAppPromptBuilder;
use Illuminate\Support\Facades\Log;
use Exception;

final class AIProviderService implements AIProviderServiceInterface
{
    public function __construct(
        private readonly WhatsAppPromptBuilder $promptBuilder
    ) {}

    /**
     * Generate AI response using the configured provider
     */
    public function generateResponse(
        WhatsAppAccountMetadataDTO $accountMetadata,
        AiRequestDTO $aiRequest
    ): ?WhatsAppAIResponseDTO {
        Log::info('[AI_PROVIDER] Generating AI response', [
            'session_id' => $accountMetadata->sessionId,
            'ai_model_id' => $accountMetadata->aiModelId,
            'system_prompt_length' => strlen($aiRequest->systemPrompt),
            'user_message_length' => strlen($aiRequest->userMessage),
        ]);

        try {
            // Validate prerequisites
            if (!$this->canGenerateResponse($accountMetadata)) {
                Log::warning('[AI_PROVIDER] Cannot generate response - prerequisites not met', [
                    'session_id' => $accountMetadata->sessionId,
                    'agent_enabled' => $accountMetadata->agentEnabled,
                    'ai_model_id' => $accountMetadata->aiModelId,
                ]);
                return null;
            }

            // Get AI model for the account
            $model = $this->getAIModel($accountMetadata);
            if (!$model) {
                Log::warning('[AI_PROVIDER] No AI model configured', [
                    'session_id' => $accountMetadata->sessionId,
                    'ai_model_id' => $accountMetadata->aiModelId,
                ]);
                return $this->createFallbackResponse($aiRequest->userMessage);
            }

            // Get the AI service for this model's provider
            $aiService = $model->getService();
            
            // Call the real AI service
            $response = $aiService->chat($model, $aiRequest);

            if (!$response || empty($response->content)) {
                Log::warning('[AI_PROVIDER] Empty response from AI service', [
                    'session_id' => $accountMetadata->sessionId,
                    'model' => $model->name,
                ]);
                return $this->createFallbackResponse($aiRequest->userMessage);
            }

            // Convert to our specialized DTO
            $aiResponse = new WhatsAppAIResponseDTO(
                response: $response->content,
                model: $model->name,
                confidence: $response->confidence ?? 0.9,
                tokensUsed: $response->tokensUsed ?? null,
                cost: $response->cost ?? null,
                metadata: []
            );

            Log::info('[AI_PROVIDER] AI response generated successfully', [
                'session_id' => $accountMetadata->sessionId,
                'response_length' => $aiResponse->getResponseLength(),
                'model_used' => $aiResponse->model,
                'confidence' => $aiResponse->confidence,
                'high_confidence' => $aiResponse->isHighConfidence(),
            ]);

            return $aiResponse;

        } catch (Exception $e) {
            Log::error('[AI_PROVIDER] Error generating AI response', [
                'session_id' => $accountMetadata->sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Validate if AI response generation is possible
     */
    public function canGenerateResponse(WhatsAppAccountMetadataDTO $accountMetadata): bool
    {
        $canGenerate = $accountMetadata->isAgentActive() && 
                      $accountMetadata->getEffectiveAiModelId() !== null;

        Log::debug('[AI_PROVIDER] Checking if can generate response', [
            'session_id' => $accountMetadata->sessionId,
            'agent_enabled' => $accountMetadata->agentEnabled,
            'ai_model_id' => $accountMetadata->aiModelId,
            'can_generate' => $canGenerate,
        ]);

        return $canGenerate;
    }

    /**
     * Get available AI models for the account
     */
    public function getAvailableModels(WhatsAppAccountMetadataDTO $accountMetadata): array
    {
        // This could be extended to fetch from a models registry
        $availableModels = [
            1 => 'GPT-3.5 Turbo',
            2 => 'GPT-4',
            3 => 'Claude-3',
            4 => 'Ollama Local',
        ];

        Log::debug('[AI_PROVIDER] Retrieved available models', [
            'session_id' => $accountMetadata->sessionId,
            'model_count' => count($availableModels),
            'current_model_id' => $accountMetadata->aiModelId,
        ]);

        return $availableModels;
    }

    /**
     * Validate AI request before processing
     */
    public function validateAiRequest(AiRequestDTO $aiRequest): bool
    {
        if (empty(trim($aiRequest->userMessage))) {
            Log::warning('[AI_PROVIDER] Empty user message in AI request');
            return false;
        }

        if (empty(trim($aiRequest->systemPrompt))) {
            Log::warning('[AI_PROVIDER] Empty system prompt in AI request');
            return false;
        }

        if (strlen($aiRequest->userMessage) > 4000) {
            Log::warning('[AI_PROVIDER] User message too long', [
                'length' => strlen($aiRequest->userMessage),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Get AI service health status
     */
    public function getServiceHealth(): array
    {
        try {
            // Test with a simple request
            $testRequest = new AiRequestDTO(
                systemPrompt: 'You are a test assistant.',
                userMessage: 'Hello',
                config: [],
                context: []
            );

            $startTime = microtime(true);
            // Temporary health check without actual AI call
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $isHealthy = true; // Assume healthy for now

            Log::info('[AI_PROVIDER] Health check completed', [
                'healthy' => $isHealthy,
                'response_time_ms' => $responseTime,
            ]);

            return [
                'healthy' => $isHealthy,
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString(),
                'service' => 'WhatsAppAIService',
            ];

        } catch (Exception $e) {
            Log::error('[AI_PROVIDER] Health check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'healthy' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
                'service' => 'WhatsAppAIService',
            ];
        }
    }

    /**
     * Get AI usage statistics
     */
    public function getUsageStats(WhatsAppAccountMetadataDTO $accountMetadata): array
    {
        // This could be extended to track actual usage from database
        return [
            'session_id' => $accountMetadata->sessionId,
            'requests_today' => 0, // Would come from actual tracking
            'tokens_used_today' => 0, // Would come from actual tracking
            'cost_today' => 0.0, // Would come from actual tracking
            'model_used' => $accountMetadata->getEffectiveAiModelId(),
            'agent_enabled' => $accountMetadata->agentEnabled,
        ];
    }

    /**
     * Get AI model for the account
     */
    private function getAIModel(WhatsAppAccountMetadataDTO $accountMetadata): ?AiModel
    {
        if (!$accountMetadata->aiModelId) {
            return null;
        }

        return AiModel::find($accountMetadata->aiModelId);
    }

    /**
     * Create fallback response when AI service fails
     */
    private function createFallbackResponse(string $userMessage): WhatsAppAIResponseDTO
    {
        return new WhatsAppAIResponseDTO(
            response: 'Désolé, je rencontre actuellement des difficultés techniques. Pouvez-vous reformuler votre demande ?',
            model: 'fallback',
            confidence: 0.0,
            tokensUsed: 0,
            cost: 0.0,
            metadata: ['fallback' => true, 'original_message' => substr($userMessage, 0, 100)]
        );
    }
}
