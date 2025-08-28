<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Constants\TimeoutLimits;
use App\DTOs\AI\AiRequestDTO;
use App\DTOs\AI\AiResponseDTO;
use App\Models\AiModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class OllamaService implements AiServiceInterface
{
    public function chat(AiModel $model, AiRequestDTO $request): AiResponseDTO
    {
        Log::info('🔄 Appel API Ollama', [
            'endpoint' => $model->endpoint_url,
            'model_identifier' => $model->model_identifier,
        ]);

        $config = array_merge(
            $this->getDefaultConfig(),
            $this->getModelConfig($model),
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
            'options' => [
                'temperature' => $config['temperature'],
                'num_predict' => $config['max_tokens'],
                'top_p' => $config['top_p'],
            ],
            'stream' => false,
        ];

        $response = Http::timeout(TimeoutLimits::HTTP_LONG_TIMEOUT)
            ->connectTimeout(TimeoutLimits::HTTP_CONNECT_TIMEOUT)
            ->retry(2, 1000)
            ->post($model->endpoint_url.'/api/chat', $payload);

        if (! $response->successful()) {
            $errorMessage = "Erreur API Ollama: {$response->status()}";

            if ($response->body()) {
                $errorData = $response->json();
                $errorMessage .= ' - '.($errorData['error'] ?? $response->body());
            }

            Log::error('❌ Erreur API Ollama', [
                'status' => $response->status(),
                'body' => $response->body(),
                'endpoint' => $model->endpoint_url.'/api/chat',
            ]);

            throw new \Exception($errorMessage);
        }

        $data = $response->json();

        if (! isset($data['message']['content'])) {
            Log::error('❌ Structure de réponse inattendue', ['data' => $data]);
            throw new \Exception('Structure de réponse Ollama inattendue');
        }

        $content = $data['message']['content'];

        return AiResponseDTO::create(
            content: $content,
            metadata: [
                'provider' => 'ollama',
                'model' => $model->model_identifier,
                'total_duration' => $data['total_duration'] ?? null,
                'load_duration' => $data['load_duration'] ?? null,
                'prompt_eval_count' => $data['prompt_eval_count'] ?? null,
                'eval_count' => $data['eval_count'] ?? null,
                'response_time' => $response->transferStats->getTransferTime() ?? null,
            ]
        );
    }

    public function validateConfiguration(AiModel $model): bool
    {
        return ! empty($model->endpoint_url) && ! empty($model->model_identifier);
    }

    public function testConnection(AiModel $model): bool
    {
        try {
            Log::info('🔍 Test connexion Ollama', [
                'endpoint' => $model->endpoint_url,
                'timeout' => TimeoutLimits::HTTP_CONNECT_TIMEOUT,
            ]);

            $response = Http::timeout(TimeoutLimits::HTTP_CONNECT_TIMEOUT)
                ->connectTimeout(TimeoutLimits::HTTP_CONNECT_TIMEOUT / 2)
                ->get($model->endpoint_url.'/api/version');

            if (! $response->successful()) {
                Log::warning('❌ Test connexion échoué', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $versionData = $response->json();
            Log::info('✅ Connexion Ollama réussie', [
                'version' => $versionData['version'] ?? 'inconnue',
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('❌ Test connexion Ollama échoué', [
                'error' => $e->getMessage(),
                'endpoint' => $model->endpoint_url,
            ]);

            return false;
        }
    }

    public function getRequiredFields(): array
    {
        return ['endpoint_url', 'model_identifier'];
    }

    public function getDefaultConfig(): array
    {
        return [
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'top_p' => 0.9,
            'stream' => false,
        ];
    }

    /**
     * Récupère la configuration du modèle en gérant les types string/array
     */
    private function getModelConfig(AiModel $model): array
    {
        if (empty($model->model_config)) {
            return [];
        }

        if (is_array($model->model_config)) {
            return $model->model_config;
        }

        if (is_string($model->model_config)) {
            $decoded = json_decode($model->model_config, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Invalid JSON configuration for model', [
                    'model_id' => $model->id,
                    'json_error' => json_last_error_msg(),
                    'raw_config' => $model->model_config,
                ]);

                return [];
            }

            return $decoded ?? [];
        }

        Log::warning('Type de configuration non supporté', [
            'model_id' => $model->id,
            'config_type' => gettype($model->model_config),
        ]);

        return [];
    }
}
