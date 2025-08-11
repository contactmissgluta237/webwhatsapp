<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiModelsSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            [
                'name' => 'GenericIA (Ollama Gemma2)',
                'provider' => 'ollama',
                'model_identifier' => 'gemma2:2b',
                'description' => 'Modèle IA interne optimisé pour les conversations WhatsApp. Rapide, efficace et économique. Idéal pour débuter.',
                'endpoint_url' => 'http://209.126.83.125:11434',
                'requires_api_key' => false,
                'api_key' => null,
                'model_config' => json_encode([
                    'temperature' => 0.7,
                    'max_tokens' => 1000,
                    'top_p' => 0.9,
                ]),
                'is_active' => true,
                'is_default' => true,
                'cost_per_1k_tokens' => 0.0,
                'max_context_length' => 8192,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ChatGPT 4o-mini',
                'provider' => 'openai',
                'model_identifier' => 'gpt-4o-mini',
                'description' => 'Modèle OpenAI rapide et économique, excellent pour les conversations générales et le support client.',
                'endpoint_url' => 'https://api.openai.com/v1',
                'requires_api_key' => true,
                'api_key' => null,
                'model_config' => json_encode([
                    'temperature' => 0.7,
                    'max_tokens' => 1500,
                    'frequency_penalty' => 0.0,
                    'presence_penalty' => 0.0,
                ]),
                'is_active' => false,
                'is_default' => false,
                'cost_per_1k_tokens' => 0.00015,
                'max_context_length' => 128000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Claude 3.5 Sonnet',
                'provider' => 'anthropic',
                'model_identifier' => 'claude-3-5-sonnet-20241022',
                'description' => 'Modèle Anthropic avancé, excellent pour les conversations naturelles et les tâches complexes.',
                'endpoint_url' => 'https://api.anthropic.com/v1',
                'requires_api_key' => true,
                'api_key' => null,
                'model_config' => json_encode([
                    'max_tokens' => 1500,
                    'temperature' => 0.7,
                    'top_p' => 0.9,
                ]),
                'is_active' => false,
                'is_default' => false,
                'cost_per_1k_tokens' => 0.003,
                'max_context_length' => 200000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'DeepSeek Chat',
                'provider' => 'deepseek',
                'model_identifier' => 'deepseek-chat',
                'description' => 'Modèle DeepSeek polyvalent et performant pour diverses tâches conversationnelles.',
                'endpoint_url' => 'https://api.deepseek.com/v1',
                'requires_api_key' => true,
                'api_key' => null,
                'model_config' => json_encode([
                    'temperature' => 0.7,
                    'max_tokens' => 1500,
                    'top_p' => 0.95,
                ]),
                'is_active' => false,
                'is_default' => false,
                'cost_per_1k_tokens' => 0.00014,
                'max_context_length' => 32000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('ai_models')->insert($models);
    }
}
