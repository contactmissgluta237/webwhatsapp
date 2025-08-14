<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Services\AI\AiConfigurationService;
use Illuminate\Support\Facades\Config;

final class AiTestHelper
{
    public static function getTestingConfig(string $provider = 'ollama'): array
    {
        return Config::get("ai.testing.{$provider}", []);
    }

    public static function getOllamaTestEndpoint(): string
    {
        return Config::get('ai.testing.ollama.endpoint_url', 'http://209.126.83.125:11434');
    }

    public static function getOllamaTestModel(): string
    {
        return Config::get('ai.testing.ollama.model_identifier', 'gemma2:2b');
    }

    public static function getMockEndpoint(string $type = 'inaccessible'): string
    {
        return Config::get("ai.testing.mock_endpoints.{$type}", 'http://localhost:99999');
    }

    public static function createTestModelData(string $provider = 'ollama', array $overrides = []): array
    {
        $providerConfig = AiConfigurationService::getProviderConfig($provider);
        
        return array_merge([
            'name' => $providerConfig['name'],
            'provider' => $provider,
            'model_identifier' => $providerConfig['model_identifier'],
            'description' => $providerConfig['description'],
            'endpoint_url' => $providerConfig['endpoint_url'],
            'requires_api_key' => $providerConfig['requires_api_key'],
            'api_key' => $providerConfig['api_key'],
            'model_config' => $providerConfig['default_config'],
            'is_active' => true,
            'is_default' => true,
            'cost_per_1k_tokens' => $providerConfig['cost_per_1k_tokens'],
            'max_context_length' => $providerConfig['max_context_length'],
        ], $overrides);
    }

    public static function createOllamaTestModel(array $overrides = []): array
    {
        return self::createTestModelData('ollama', $overrides);
    }

    public static function createOpenaiTestModel(array $overrides = []): array
    {
        return self::createTestModelData('openai', $overrides);
    }

    public static function createAnthropicTestModel(array $overrides = []): array
    {
        return self::createTestModelData('anthropic', $overrides);
    }

    public static function createDeepseekTestModel(array $overrides = []): array
    {
        return self::createTestModelData('deepseek', $overrides);
    }
}
