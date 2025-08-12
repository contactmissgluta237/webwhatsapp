<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\AI;

use App\DTOs\AI\AiRequestDTO;
use App\Enums\AiProvider;
use App\Enums\ResponseTime;
use App\Models\AiModel;
use Illuminate\Support\Facades\Log;

final class AiResponseSimulator
{
    public function simulate(AiModel $model, string $prompt, string $userMessage, ResponseTime $responseTime): string
    {
        Log::info('🤖 Début simulation IA', [
            'model_name' => $model->name,
            'provider' => $model->provider,
            'user_message' => $userMessage,
            'prompt_length' => strlen($prompt),
            'response_time' => $responseTime->value,
        ]);

        try {
            Log::info('🔍 Création provider enum', [
                'model_provider' => $model->provider,
                'model_provider_type' => gettype($model->provider),
                'is_empty' => empty($model->provider),
            ]);

            $provider = AiProvider::from($model->provider);
            Log::info('✅ Provider créé', ['provider' => $provider->value]);

            $aiService = $provider->createService();
            Log::info('✅ Service créé', ['service_class' => get_class($aiService)]);

            $request = new AiRequestDTO(
                systemPrompt: $prompt,
                userMessage: $userMessage,
                config: [],
                context: []
            );

            $response = $aiService->chat($model, $request);

            Log::info('✅ Réponse IA générée', [
                'model_name' => $model->name,
                'response_length' => strlen($response->content),
                'success' => true,
            ]);

            return $response->content;

        } catch (\Exception $e) {
            Log::error('❌ Erreur simulation IA', [
                'model_name' => $model->name,
                'error' => $e->getMessage(),
                'user_message' => $userMessage,
            ]);

            throw $e;
        }
    }
}
