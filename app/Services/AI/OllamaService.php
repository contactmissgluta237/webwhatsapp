<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class OllamaService implements OllamaServiceInterface
{
    private PendingRequest $httpClient;

    public function __construct()
    {
        $this->httpClient = Http::baseUrl(config('services.ollama.url'))
            ->timeout(120)
            ->retry(2, 1000);
    }

    public function generateResponse(string $prompt, array $options = []): array
    {
        try {
            $model = $options['model'] ?? config('services.ollama.default_model', 'llama2:7b-chat');

            $response = $this->httpClient->post('/api/generate', [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => $options['temperature'] ?? 0.7,
                    'top_k' => $options['top_k'] ?? 40,
                    'top_p' => $options['top_p'] ?? 0.9,
                ],
            ]);

            if (! $response->successful()) {
                throw new \Exception('Ollama API error: '.$response->body());
            }

            $data = $response->json();

            return [
                'response' => $data['response'] ?? '',
                'model' => $model,
                'confidence' => $this->calculateConfidence($data),
                'tokens_generated' => $data['eval_count'] ?? null,
                'generation_time' => $data['total_duration'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Ollama generation failed', [
                'prompt' => substr($prompt, 0, 100).'...',
                'error' => $e->getMessage(),
            ]);

            return [
                'response' => 'Désolé, je ne peux pas répondre pour le moment. Veuillez réessayer plus tard.',
                'model' => 'fallback',
                'confidence' => 0.0,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function isHealthy(): bool
    {
        try {
            $response = $this->httpClient->get('/api/tags');

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Ollama health check failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function getAvailableModels(): array
    {
        try {
            $response = $this->httpClient->get('/api/tags');

            if (! $response->successful()) {
                return [];
            }

            $data = $response->json();

            return collect($data['models'] ?? [])
                ->map(fn ($model) => [
                    'name' => $model['name'],
                    'size' => $model['size'] ?? 0,
                    'modified_at' => $model['modified_at'] ?? null,
                ])
                ->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to get Ollama models', ['error' => $e->getMessage()]);

            return [];
        }
    }

    private function calculateConfidence(array $response): float
    {
        if (! isset($response['response']) || empty($response['response'])) {
            return 0.0;
        }

        $responseLength = strlen($response['response']);
        $tokens = $response['eval_count'] ?? 1;

        $baseConfidence = min(0.9, $responseLength / 200);

        if ($tokens > 10) {
            $baseConfidence += 0.1;
        }

        return round($baseConfidence, 2);
    }
}
