<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Constants\TimeoutLimits;
use App\DTOs\AI\AiRequestDTO;
use App\DTOs\AI\AiResponseDTO;
use App\Models\AiModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class OpenAiService implements AiServiceInterface
{
    public function chat(AiModel $model, AiRequestDTO $request): AiResponseDTO
    {
        if (! $model->api_key) {
            throw new \Exception('Clé API OpenAI manquante');
        }

        Log::info('🔄 Appel API OpenAI', [
            'model' => $model->model_identifier,
        ]);

        $config = array_merge(
            $this->getDefaultConfig(),
            json_decode($model->model_config ?? '{}', true),
            $request->config
        );

        $payload = [
            'model' => $model->model_identifier,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $request->systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $request->userMessage,
                ],
            ],
            'temperature' => $config['temperature'],
            'max_tokens' => $config['max_tokens'],
            'frequency_penalty' => $config['frequency_penalty'],
            'presence_penalty' => $config['presence_penalty'],
        ];

        $response = Http::timeout(TimeoutLimits::HTTP_REQUEST_TIMEOUT)
            ->withHeaders([
                'Authorization' => 'Bearer '.$model->api_key,
                'Content-Type' => 'application/json',
            ])
            ->post($model->endpoint_url.'/chat/completions', $payload);

        if (! $response->successful()) {
            throw new \Exception("Erreur API OpenAI: {$response->status()} - {$response->body()}");
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? throw new \Exception('Réponse vide d\'OpenAI');

        return AiResponseDTO::create(
            content: $content,
            metadata: [
                'provider' => 'openai',
                'model' => $model->model_identifier,
                'usage' => $data['usage'] ?? [],
            ]
        );
    }

    public function validateConfiguration(AiModel $model): bool
    {
        return ! empty($model->api_key) && ! empty($model->model_identifier);
    }

    public function testConnection(AiModel $model): bool
    {
        try {
            $response = Http::timeout(TimeoutLimits::HTTP_CONNECT_TIMEOUT / 2)
                ->withHeaders(['Authorization' => 'Bearer '.$model->api_key])
                ->get($model->endpoint_url.'/models');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Test connexion OpenAI échoué', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function getRequiredFields(): array
    {
        return ['api_key', 'model_identifier'];
    }

    public function getDefaultConfig(): array
    {
        return [
            'temperature' => 0.7,
            'max_tokens' => 1500,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0,
        ];
    }
}
