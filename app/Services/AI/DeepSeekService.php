<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTOs\AI\AiRequestDTO;
use App\DTOs\AI\AiResponseDTO;
use App\Models\AiModel;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\DTOs\WhatsApp\ConversationContextDTO;

final class DeepSeekService implements AiServiceInterface
{
    public function chat(AiModel $model, AiRequestDTO $request): AiResponseDTO
    {
        return $this->generate($request, $model);
    }

    public function generate(AiRequestDTO $request, AiModel $model): AiResponseDTO
    {
        $this->validateConfiguration($model);
        $messages = $this->prepareMessages($request->systemPrompt, $request->userMessage, $request->context);

        Log::info('🔄 Appel API DeepSeek', [
            'endpoint' => $model->endpoint_url ?? 'https://api.deepseek.com',
            'model_identifier' => $model->model_identifier,
            'message_count' => count($messages),
            'system_prompt_length' => strlen($request->systemPrompt),
            'user_message_length' => strlen($request->userMessage),
            'context_count' => count($request->context),
            'request_config_type' => gettype($request->config),
            'request_config_content' => $request->config,
            'model_config_type' => gettype($model->model_config),
        ]);

        $config = array_merge(
            $this->getDefaultConfig(),
            is_string($model->model_config) ? json_decode($model->model_config, true) : ($model->model_config ?? []),
            is_array($request->config) ? $request->config : []
        );

        $payload = [
            'model' => $model->model_identifier,
            'messages' => $messages,
            'temperature' => $config['temperature'],
            'max_tokens' => $config['max_tokens'],
            'top_p' => $config['top_p'],
            'stream' => false,
        ];

        Log::debug('📤 DeepSeek API Request Payload', $payload);

        return $this->executeWithRetry($model, $payload, $this->getTimeoutForOperation($request));
    }

    private function executeWithRetry(AiModel $model, array $payload, int $timeout, int $maxRetries = 2): AiResponseDTO
    {
        $baseUrl = rtrim($model->endpoint_url ?? 'https://api.deepseek.com', '/');
        $endpoint = $baseUrl.'/chat/completions';
        $apiKey = $model->api_key ?? config('services.deepseek.api_key');

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                Log::info('🚀 Tentative API DeepSeek', [
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'timeout' => $timeout,
                    'endpoint' => $endpoint,
                ]);

                $response = Http::withToken($apiKey)
                    ->timeout($timeout)
                    ->post($endpoint, $payload);

                $response->throw();

                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';
                $usage = $data['usage'] ?? [];

                Log::info('✅ Réponse DeepSeek reçue', [
                    'attempt' => $attempt,
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
                        'confidence' => 1.0,
                        'attempts' => $attempt,
                    ]
                );

            } catch (RequestException $e) {
                $isLastAttempt = $attempt === $maxRetries;
                $isTimeoutError = str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'timed out');

                Log::warning('⚠️ Tentative DeepSeek échouée', [
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'is_last_attempt' => $isLastAttempt,
                    'is_timeout_error' => $isTimeoutError,
                    'error' => $e->getMessage(),
                    'response' => $e->response ? $e->response->body() : 'No response',
                ]);

                if ($isLastAttempt) {
                    Log::error('❌ Erreur API DeepSeek - Toutes les tentatives échouées', [
                        'total_attempts' => $attempt,
                        'timeout' => $timeout,
                        'error' => $e->getMessage(),
                    ]);
                    throw new \Exception("Erreur de communication avec l'API DeepSeek après {$attempt} tentatives: ".$e->getMessage(), $e->getCode(), $e);
                }

                if ($isTimeoutError && $attempt < $maxRetries) {
                    $backoffSeconds = min(5 * $attempt, 15);
                    Log::info("⏳ Attente avant retry : {$backoffSeconds}s");
                    sleep($backoffSeconds);
                }
            }
        }

        throw new \Exception('Erreur inattendue: toutes les tentatives ont échoué sans exception appropriée');
    }

    private function getTimeoutForOperation(AiRequestDTO $request): int
    {
        $systemPromptLength = strlen($request->systemPrompt);
        $userMessageLength = strlen($request->userMessage);
        $totalLength = $systemPromptLength + $userMessageLength;

        if ($this->isPromptEnhancement($request)) {
            return config('ai.deepseek.timeout.prompt_enhancement', 90);
        }

        if ($totalLength > 2000) {
            return config('ai.deepseek.timeout.long_requests', 60);
        }

        return config('ai.deepseek.timeout.default', 30);
    }

    private function isPromptEnhancement(AiRequestDTO $request): bool
    {
        return str_contains($request->systemPrompt, 'améliorer le prompt') ||
               str_contains($request->systemPrompt, 'prompt pour agent') ||
               str_contains($request->userMessage, 'prompt à améliorer');
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
            $endpoint = $baseUrl.'/chat/completions';
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

    private function prepareMessages(string $systemPrompt, string $userMessage, array $context): array
    {
        $messages = [];

        // Add system message with conversation context already included
        $messages[] = [
            'role' => 'system',
            'content' => $systemPrompt // SystemPrompt contains formatted conversation history
        ];

        // Add current user message
        $messages[] = [
            'role' => 'user', 
            'content' => $userMessage
        ];

        return $messages;
    }
}
