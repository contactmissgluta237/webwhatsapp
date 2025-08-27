<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Contracts\WhatsApp\AIProviderServiceInterface;
use App\DTOs\AI\AiRequestDTO;
use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\Models\WhatsAppAccount;
use Exception;
use Illuminate\Support\Facades\Log;

final class AIProviderService implements AIProviderServiceInterface
{
    private const FALLBACK_MESSAGE = 'Désolé, je rencontre actuellement des difficultés techniques. Pouvez-vous reformuler votre demande ?';

    /**
     * Generate AI response using the configured provider
     */
    public function generateResponse(AiRequestDTO $aiRequest): ?WhatsAppAIResponseDTO
    {
        Log::info('[AI_PROVIDER] Generating AI response', [
            'account_id' => $aiRequest->account->id,
            'ai_model_id' => $aiRequest->account->ai_model_id,
            'system_prompt_length' => strlen($aiRequest->systemPrompt),
            'user_message_length' => strlen($aiRequest->userMessage),
        ]);

        try {
            return $this->callAIService($aiRequest);
        } catch (Exception $e) {
            Log::error('[AI_PROVIDER] Error generating response', [
                'account_id' => $aiRequest->account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->createFallbackResponse($aiRequest->account);
        }
    }

    /**
     * Call AI service with proper error handling
     */
    private function callAIService(AiRequestDTO $aiRequest): WhatsAppAIResponseDTO
    {
        $model = $aiRequest->account->aiModel;

        $response = ($model->getService())->chat($model, $aiRequest);

        if (! $response || empty($response->content)) {
            Log::warning('[AI_PROVIDER] Empty response from AI service', [
                'account_id' => $aiRequest->account->id,
                'model' => $model->name,
            ]);

            return $this->createFallbackResponse($aiRequest->account, $model->name);
        }

        $aiResponseDTO = new WhatsAppAIResponseDTO(
            response: $response->content,
            model: $model->name,
            confidence: $response->confidence ?? null,
            tokensUsed: $response->tokens_used ?? null,
            cost: $response->cost ?? null,
            metadata: array_merge([
                'provider' => $model->provider,
                'model_id' => $model->id,
                'account_id' => $aiRequest->account->id,
                'timestamp' => now()->toISOString(),
            ], $response->metadata ?? [])
        );

        Log::info('[AI_PROVIDER] AI response generated successfully', [
            'account_id' => $aiRequest->account->id,
            'response_length' => $aiResponseDTO->getResponseLength(),
            'model_used' => $aiResponseDTO->model,
            'high_confidence' => $aiResponseDTO->isHighConfidence(),
            'tokens_used' => $aiResponseDTO->tokensUsed,
        ]);

        return $aiResponseDTO;
    }

    /**
     * Create fallback response when AI service fails
     */
    private function createFallbackResponse(WhatsAppAccount $account, ?string $modelName = null): WhatsAppAIResponseDTO
    {
        return new WhatsAppAIResponseDTO(
            response: self::FALLBACK_MESSAGE,
            model: $modelName ?? 'fallback',
            confidence: 0.0,
            tokensUsed: 0,
            cost: 0.0,
            metadata: [
                'is_fallback' => true,
                'account_id' => $account->id,
                'timestamp' => now()->toISOString(),
            ]
        );
    }
}
