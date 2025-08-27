<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Facades\Config;

final class AiConfigurationService
{
    public static function getProviderConfig(string $provider): array
    {
        return Config::get("ai.providers.{$provider}", []);
    }

    public static function getDefaultModels(): array
    {
        $defaultModels = [];

        // Only seed the default provider (DeepSeek)
        $defaultProvider = Config::get('ai.default_provider');
        $enabledProviders = [$defaultProvider]; // Only enable the default provider

        // Uncomment other providers if needed in the future
        // $enabledProviders = ['deepseek', 'openai', 'anthropic', 'ollama'];

        foreach (Config::get('ai.providers', []) as $provider => $config) {
            if (in_array($provider, $enabledProviders)) {
                $defaultModels[] = [
                    'name' => $config['name'],
                    'provider' => $provider,
                    'model_identifier' => $config['model_identifier'],
                    'description' => $config['description'],
                    'endpoint_url' => $config['endpoint_url'],
                    'requires_api_key' => $config['requires_api_key'],
                    'api_key' => $config['api_key'],
                    'model_config' => json_encode($config['default_config']),
                    'is_active' => true,
                    'is_default' => $provider === $defaultProvider,
                    'cost_per_1k_tokens' => $config['cost_per_1k_tokens'],
                    'max_context_length' => $config['max_context_length'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        return $defaultModels;
    }
}
