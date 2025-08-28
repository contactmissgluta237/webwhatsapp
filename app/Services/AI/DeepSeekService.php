<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Constants\ApplicationLimits;
use App\Constants\TimeoutLimits;
use App\DTOs\AI\AiRequestDTO;
use App\DTOs\AI\AiResponseDTO;
use App\Enums\SimulatorMessageType;
use App\Models\AiModel;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class DeepSeekService implements AiServiceInterface
{
    private const DEFAULT_ENDPOINT = 'https://api.deepseek.com';
    private const COST_PER_1M_PROMPT = 0.14;
    private const COST_PER_1M_COMPLETION = 0.28;
    private const COST_PER_1M_CACHED = 0.014;
    private const USD_TO_XAF_RATE = 650;

    public function chat(AiModel $model, AiRequestDTO $request): AiResponseDTO
    {
        return $this->generate($request, $model);
    }

    public function generate(AiRequestDTO $request, AiModel $model): AiResponseDTO
    {
        $this->validateConfiguration($model);
        $messages = $this->prepareMessages($request->systemPrompt, $request->userMessage);

        Log::info('🔄 DeepSeek API Call', [
            'endpoint' => $model->endpoint_url ?? self::DEFAULT_ENDPOINT,
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
        $baseUrl = rtrim($model->endpoint_url ?? self::DEFAULT_ENDPOINT, '/');
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

                $promptTokens = $usage['prompt_tokens'] ?? 0;
                $completionTokens = $usage['completion_tokens'] ?? 0;
                $cachedTokens = $usage['prompt_cache_hit_tokens'] ?? 0;

                $costs = $this->calculateCosts($promptTokens, $completionTokens, $cachedTokens);

                Log::info('✅ DeepSeek Response Received', [
                    'attempt' => $attempt,
                    'model' => $data['model'] ?? $model->model_identifier,
                    'usage' => $usage,
                    'content_length' => strlen($content),
                    'costs' => $costs,
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
                        'costs' => $costs,
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
            return config('ai.deepseek.timeout.long_requests', TimeoutLimits::HTTP_LONG_TIMEOUT);
        }

        return config('ai.deepseek.timeout.default', TimeoutLimits::HTTP_REQUEST_TIMEOUT);
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
            $baseUrl = rtrim($model->endpoint_url ?? self::DEFAULT_ENDPOINT, '/');
            $endpoint = $baseUrl.'/chat/completions';
            $apiKey = $model->api_key ?? config('services.deepseek.api_key');

            Log::info('🔍 DeepSeek Connection Test', [
                'endpoint' => $endpoint,
                'timeout' => TimeoutLimits::HTTP_REQUEST_TIMEOUT,
            ]);

            $response = Http::withToken($apiKey)
                ->timeout(TimeoutLimits::HTTP_REQUEST_TIMEOUT)
                ->post($endpoint, [
                    'model' => $model->model_identifier,
                    'messages' => [['role' => SimulatorMessageType::USER()->value, 'content' => 'Test']],
                    'max_tokens' => ApplicationLimits::AI_TEST_MAX_TOKENS,
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

        $messages[] = [
            'role' => 'system',
            'content' => $systemPrompt,
        ];

        $messages[] = [
            'role' => SimulatorMessageType::USER()->value,
            'content' => $userMessage,
        ];

        return $messages;
    }

    private function calculateCosts(int $promptTokens, int $completionTokens, int $cachedTokens): array
    {
        $promptCost = ($promptTokens - $cachedTokens) * self::COST_PER_1M_PROMPT / 1000000;
        $completionCost = $completionTokens * self::COST_PER_1M_COMPLETION / 1000000;
        $cachedCost = $cachedTokens * self::COST_PER_1M_CACHED / 1000000;
        $totalCostUSD = $promptCost + $completionCost + $cachedCost;

        return [
            'prompt_cost_usd' => round($promptCost, 6),
            'completion_cost_usd' => round($completionCost, 6),
            'cached_cost_usd' => round($cachedCost, 6),
            'total_cost_usd' => round($totalCostUSD, 6),
            'total_cost_xaf' => round($totalCostUSD * self::USD_TO_XAF_RATE, 2),
        ];
    }
}
