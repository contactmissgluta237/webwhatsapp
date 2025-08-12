<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AiProvider;
use App\Models\AiModel;
use Illuminate\Database\Seeder;

final class OllamaModelSeeder extends Seeder
{
    public function run(): void
    {
        AiModel::updateOrCreate(
            [
                'provider' => AiProvider::OLLAMA(),
                'model_identifier' => 'gemma2:2b',
            ],
            [
                'name' => 'Gemma 2B (Ollama)',
                'description' => 'Modèle Gemma 2B hébergé sur Ollama - Rapide et efficace',
                'endpoint_url' => 'http://209.126.83.125:11434',
                'requires_api_key' => false,
                'api_key' => null,
                'model_config' => [
                    'temperature' => 0.7,
                    'max_tokens' => 1000,
                    'top_p' => 0.9,
                ],
                'is_active' => true,
                'is_default' => false,
                'cost_per_1k_tokens' => 0.0,
                'max_context_length' => 4096,
            ]
        );

        AiModel::updateOrCreate(
            [
                'provider' => AiProvider::OLLAMA(),
                'model_identifier' => 'llama3.1:8b',
            ],
            [
                'name' => 'Llama 3.1 8B (Ollama)',
                'description' => 'Modèle Llama 3.1 8B hébergé sur Ollama - Plus puissant',
                'endpoint_url' => 'http://209.126.83.125:11434',
                'requires_api_key' => false,
                'api_key' => null,
                'model_config' => [
                    'temperature' => 0.7,
                    'max_tokens' => 2000,
                    'top_p' => 0.9,
                ],
                'is_active' => true,
                'is_default' => true,
                'cost_per_1k_tokens' => 0.0,
                'max_context_length' => 8192,
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
                'endpoint_url' => 'http://209.126.83.125:11434',
                'requires_api_key' => false,
                'api_key' => null,
                'model_config' => [
                    'temperature' => 0.7,
                    'max_tokens' => 1500,
                    'top_p' => 0.9,
                ],
                'is_active' => true,
                'is_default' => false,
                'cost_per_1k_tokens' => 0.0,
                'max_context_length' => 4096,
            ]
        );
    }
}
