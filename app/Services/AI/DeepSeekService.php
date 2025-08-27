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
        return $this->generate($request, $model);
    }

    public function generate(AiRequestDTO $request, AiModel $model): AiResponseDTO
    {
        $this->validateConfiguration($model);
        $messages = $this->prepareMessages($request->systemPrompt, $request->userMessage);

        Log::info('🔄 DeepSeek API Call', [
            'endpoint' => $model->endpoint_url ?? 'https://api.deepseek.com',
            'model_identifier' => $model->model_identifier,
            'message_count' => count($messages),
            'system_prompt_length' => strlen($request->systemPrompt),
            'user_message_length' => strlen($request->userMessage),
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
                Log::info('🚀 DeepSeek API Attempt', [
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

                // Calculate costs based on DeepSeek pricing
                $promptTokens = $usage['prompt_tokens'] ?? 0;
                $completionTokens = $usage['completion_tokens'] ?? 0;
                $cachedTokens = $usage['prompt_cache_hit_tokens'] ?? 0;

                // DeepSeek pricing per 1M tokens
                $promptCostPer1M = 0.14;     // $0.14 per 1M prompt tokens
                $completionCostPer1M = 0.28; // $0.28 per 1M completion tokens
                $cachedCostPer1M = 0.014;    // $0.014 per 1M cached tokens (90% discount)

                $promptCost = ($promptTokens - $cachedTokens) * $promptCostPer1M / 1000000;
                $completionCost = $completionTokens * $completionCostPer1M / 1000000;
                $cachedCost = $cachedTokens * $cachedCostPer1M / 1000000;
                $totalCostUSD = $promptCost + $completionCost + $cachedCost;

                Log::info('✅ DeepSeek Response Received', [
                    'attempt' => $attempt,
                    'model' => $data['model'] ?? $model->model_identifier,
                    'usage' => $usage,
                    'content_length' => strlen($content),
                    'costs' => [
                        'prompt_cost_usd' => round($promptCost, 6),
                        'completion_cost_usd' => round($completionCost, 6),
                        'cached_cost_usd' => round($cachedCost, 6),
                        'total_cost_usd' => round($totalCostUSD, 6),
                        'total_cost_xaf' => round($totalCostUSD * 650, 2), // ~650 XAF per USD
                    ],
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
                        'costs' => [
                            'prompt_cost_usd' => round($promptCost, 6),
                            'completion_cost_usd' => round($completionCost, 6),
                            'cached_cost_usd' => round($cachedCost, 6),
                            'total_cost_usd' => round($totalCostUSD, 6),
                            'total_cost_xaf' => round($totalCostUSD * 650, 2),
                        ],
                    ]
                );

            } catch (RequestException $e) {
                $isLastAttempt = $attempt === $maxRetries;
                $isTimeoutError = str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'timed out');

                Log::warning('⚠️ DeepSeek Attempt Failed', [
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'is_last_attempt' => $isLastAttempt,
                    'is_timeout_error' => $isTimeoutError,
                    'error' => $e->getMessage(),
                    'response' => $e->response ? $e->response->body() : 'No response',
                ]);

                if ($isLastAttempt) {
                    Log::error('❌ DeepSeek API Error - All Attempts Failed', [
                        'total_attempts' => $attempt,
                        'timeout' => $timeout,
                        'error' => $e->getMessage(),
                    ]);
                    throw new \Exception("Communication error with DeepSeek API after {$attempt} attempts: ".$e->getMessage(), $e->getCode(), $e);
                }

                if ($isTimeoutError && $attempt < $maxRetries) {
                    $backoffSeconds = min(5 * $attempt, 15);
                    Log::info("⏳ Waiting before retry: {$backoffSeconds}s");
                    sleep($backoffSeconds);
                }
            }
        }

        throw new \Exception('Unexpected error: all attempts failed without appropriate exception');
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
        return str_contains($request->systemPrompt, 'enhance the prompt') ||
               str_contains($request->systemPrompt, 'prompt for agent') ||
               str_contains($request->userMessage, 'prompt to enhance');
    }

    public function validateConfiguration(AiModel $model): bool
    {
        $apiKey = $model->api_key ?? config('services.deepseek.api_key');
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('DeepSeek API key is not configured.');
        }

        if (empty($model->model_identifier)) {
            throw new \InvalidArgumentException('DeepSeek model identifier is not configured.');
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

            Log::info('🔍 DeepSeek Connection Test', [
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
                Log::info('✅ DeepSeek Connection Successful');

                return true;
            }

            Log::warning('❌ DeepSeek Connection Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('❌ Critical error DeepSeek connection test', ['message' => $e->getMessage()]);

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

    private function prepareMessages(string $systemPrompt, string $userMessage): array
    {
        $messages = [];

        // Add system message with conversation context already included
        $messages[] = [
            'role' => 'system',
            'content' => $systemPrompt, // SystemPrompt contains formatted conversation history
        ];

        // Add current user message
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        return $messages;
    }
}
