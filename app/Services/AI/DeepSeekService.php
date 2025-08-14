<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTOs\AI\AiRequestDTO;
use App\DTOs\AI\AiResponseDTO;
use App\Models\AiModel;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class DeepSeekService implements AiServiceInterface
{
    public function chat(AiModel $model, AiRequestDTO $request): AiResponseDTO
    {
        $this->validateConfiguration($model);

        $baseUrl = rtrim($model->endpoint_url ?? 'https://api.deepseek.com', '/');
        $endpoint = $baseUrl . '/chat/completions';
        $apiKey = $model->api_key ?? config('services.deepseek.api_key');

        $messages = $this->prepareMessages($request);
        $config = $model->getMergedConfig($request->config);

        Log::info('🔄 Appel API DeepSeek', [
            'endpoint' => $endpoint,
            'model_identifier' => $model->model_identifier,
            'message_count' => count($messages),
        ]);

        try {
            $response = Http::withToken($apiKey)
                ->timeout(120)
                ->post($endpoint, [
                    'model' => $model->model_identifier,
                    'messages' => $messages,
                    'temperature' => (float) $config['temperature'],
                    'max_tokens' => (int) $config['max_tokens'],
                    'top_p' => (float) $config['top_p'],
                    'stream' => false,
                ]);

            $response->throw();

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';
            $usage = $data['usage'] ?? [];

            Log::info('✅ Réponse DeepSeek reçue', [
                'model' => $data['model'] ?? $model->model_identifier,
                'usage' => $usage,
                'content_length' => strlen($content),
            ]);

            return new AiResponseDTO(
                content: $content,
                tokensUsed: $usage['total_tokens'] ?? 0,
                metadata: [
                    'provider' => 'deepseek',
                    'model_name' => $model->name,
                    'model' => $data['model'] ?? $model->model_identifier,
                    'usage' => $usage,
                    'confidence' => 1.0, // Not provided by API, default to 1.0
                ]
            );

        } catch (RequestException $e) {
            Log::error('❌ Erreur API DeepSeek', [
                'message' => $e->getMessage(),
                'response' => $e->response ? $e->response->body() : 'No response',
            ]);
            throw new \Exception("Erreur de communication avec l'API DeepSeek: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function validateConfiguration(AiModel $model): bool
    {
        $apiKey = $model->api_key ?? config('services.deepseek.api_key');
        if (empty($apiKey)) {
            throw new \InvalidArgumentException("La clé API DeepSeek n'est pas configurée.");
        }

        if (empty($model->model_identifier)) {
            throw new \InvalidArgumentException("L'identifiant du modèle DeepSeek n'est pas configuré.");
        }

        return true;
    }

    public function testConnection(AiModel $model): bool
    {
        try {
            $this->validateConfiguration($model);
            $baseUrl = rtrim($model->endpoint_url ?? 'https://api.deepseek.com', '/');
            $endpoint = $baseUrl . '/chat/completions';
            $apiKey = $model->api_key ?? config('services.deepseek.api_key');

            Log::info('🔍 Test connexion DeepSeek', [
                'endpoint' => $endpoint,
                'timeout' => 30,
            ]);

            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post($endpoint, [
                    'model' => $model->model_identifier,
                    'messages' => [['role' => 'user', 'content' => 'Test']],
                    'max_tokens' => 10,
                ]);

            if ($response->successful()) {
                Log::info('✅ Connexion DeepSeek réussie');
                return true;
            }

            Log::warning('❌ Test connexion DeepSeek échoué', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('❌ Erreur critique test connexion DeepSeek', ['message' => $e->getMessage()]);

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
            'model' => 'deepseek-chat',
            'temperature' => 0.7,
            'max_tokens' => 1500,
            'top_p' => 0.95,
        ];
    }

    private function prepareMessages(AiRequestDTO $request): array
    {
        $messages = [];

        if ($request->systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $request->systemPrompt];
        }

        foreach ($request->context as $message) {
            $role = $message['is_user'] ? 'user' : 'assistant';
            $messages[] = ['role' => $role, 'content' => $message['message']];
        }

        $messages[] = ['role' => 'user', 'content' => $request->userMessage];

        return $messages;
    }
}
