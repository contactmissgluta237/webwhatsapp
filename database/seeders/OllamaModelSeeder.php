<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AiProvider;
use App\Models\AiModel;
use App\Services\AI\AiConfigurationService;
use Illuminate\Database\Seeder;

final class OllamaModelSeeder extends Seeder
{
    public function run(): void
    {
        $ollamaConfig = AiConfigurationService::getProviderConfig('ollama');

        AiModel::updateOrCreate(
            [
                'provider' => AiProvider::OLLAMA(),
                'model_identifier' => $ollamaConfig['model_identifier'],
            ],
            [
                'name' => $ollamaConfig['name'],
                'description' => $ollamaConfig['description'],
                'endpoint_url' => $ollamaConfig['endpoint_url'],
                'requires_api_key' => $ollamaConfig['requires_api_key'],
                'api_key' => $ollamaConfig['api_key'],
                'model_config' => $ollamaConfig['default_config'],
                'is_active' => true,
                'is_default' => false,
                'cost_per_1k_tokens' => $ollamaConfig['cost_per_1k_tokens'],
                'max_context_length' => $ollamaConfig['max_context_length'],
            ]
        );

        // Autres modèles Ollama alternatifs
        AiModel::updateOrCreate(
            [
                'provider' => AiProvider::OLLAMA(),
                'model_identifier' => 'llama3.1:8b',
            ],
            [
                'name' => 'Llama 3.1 8B (Ollama)',
                'description' => 'Modèle Llama 3.1 8B hébergé sur Ollama - Plus puissant',
                'endpoint_url' => $ollamaConfig['endpoint_url'],
                'requires_api_key' => $ollamaConfig['requires_api_key'],
                'api_key' => $ollamaConfig['api_key'],
                'model_config' => [
                    'temperature' => 0.7,
                    'max_tokens' => 2000,
                    'top_p' => 0.9,
                ],
                'is_active' => true,
                'is_default' => false,
                'cost_per_1k_tokens' => $ollamaConfig['cost_per_1k_tokens'],
                'max_context_length' => $ollamaConfig['max_context_length'],
            ]
        );

        AiModel::updateOrCreate(
            [
                'provider' => AiProvider::OLLAMA(),
                'model_identifier' => 'mistral:latest',
            ],
            [
                'name' => 'Mistral Latest (Ollama)',
                'description' => 'Modèle Mistral latest hébergé sur Ollama',
                'endpoint_url' => $ollamaConfig['endpoint_url'],
                'requires_api_key' => $ollamaConfig['requires_api_key'],
                'api_key' => $ollamaConfig['api_key'],
                'model_config' => [
                    'temperature' => 0.7,
                    'max_tokens' => 1500,
                    'top_p' => 0.9,
                ],
                'is_active' => true,
                'is_default' => false,
                'cost_per_1k_tokens' => $ollamaConfig['cost_per_1k_tokens'],
                'max_context_length' => $ollamaConfig['max_context_length'],
            ]
        );
    }
}
