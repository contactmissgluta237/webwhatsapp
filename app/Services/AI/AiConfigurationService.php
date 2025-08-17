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

        foreach (Config::get('ai.providers', []) as $provider => $config) {
            $defaultModels[] = [
                'name' => $config['name'],
                'provider' => $provider,
                'model_identifier' => $config['model_identifier'],
                'description' => $config['description'],
                'endpoint_url' => $config['endpoint_url'],
                'requires_api_key' => $config['requires_api_key'],
                'api_key' => $config['api_key'],
                'model_config' => json_encode($config['default_config']),
                'is_active' => true, // Activer tous les modèles par défaut
                'is_default' => $provider === Config::get('ai.default_provider'), // Garder un seul défaut
                'cost_per_1k_tokens' => $config['cost_per_1k_tokens'],
                'max_context_length' => $config['max_context_length'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $defaultModels;
    }
}
