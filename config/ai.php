<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | AI Services Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration centralisée pour tous les services d'IA.
    | Cette configuration est utilisée par les seeders, tests et services.
    |
    */

    'default_provider' => env('AI_DEFAULT_PROVIDER', 'deepseek'),

    'providers' => [
        'ollama' => [
            'name' => 'GenericIA (Ollama Gemma2)',
            'endpoint_url' => env('OLLAMA_ENDPOINT_URL', 'http://209.126.83.125:11434'),
            'model_identifier' => env('OLLAMA_DEFAULT_MODEL', 'gemma2:2b'),
            'requires_api_key' => false,
            'api_key' => null,
            'description' => 'Modèle IA interne optimisé pour les conversations WhatsApp. Rapide, efficace et économique. Idéal pour débuter.',
            'cost_per_1k_tokens' => 0.0,
            'max_context_length' => 8192,
            'default_config' => [
                'temperature' => 0.7,
                'max_tokens' => 1000,
                'top_p' => 0.9,
            ],
        ],

        'openai' => [
            'name' => 'ChatGPT 4o-mini',
            'endpoint_url' => env('OPENAI_ENDPOINT_URL', 'https://api.openai.com/v1'),
            'model_identifier' => env('OPENAI_DEFAULT_MODEL', 'gpt-4o-mini'),
            'requires_api_key' => true,
            'api_key' => env('OPENAI_API_KEY'),
            'description' => 'Modèle OpenAI rapide et économique, excellent pour les conversations générales et le support client.',
            'cost_per_1k_tokens' => 0.00015,
            'max_context_length' => 128000,
            'default_config' => [
                'temperature' => 0.7,
                'max_tokens' => 1500,
                'frequency_penalty' => 0.0,
                'presence_penalty' => 0.0,
            ],
        ],

        'anthropic' => [
            'name' => 'Claude 3.5 Sonnet',
            'endpoint_url' => env('ANTHROPIC_ENDPOINT_URL', 'https://api.anthropic.com/v1'),
            'model_identifier' => env('ANTHROPIC_DEFAULT_MODEL', 'claude-3-5-sonnet-20241022'),
            'requires_api_key' => true,
            'api_key' => env('ANTHROPIC_API_KEY'),
            'description' => 'Modèle Anthropic avancé, excellent pour les conversations naturelles et les tâches complexes.',
            'cost_per_1k_tokens' => 0.003,
            'max_context_length' => 200000,
            'default_config' => [
                'max_tokens' => 1500,
                'temperature' => 0.7,
                'top_p' => 0.9,
            ],
        ],

        'deepseek' => [
            'name' => 'DeepSeek Chat',
            'endpoint_url' => env('DEEPSEEK_ENDPOINT_URL', 'https://api.deepseek.com/v1'),
            'model_identifier' => env('DEEPSEEK_DEFAULT_MODEL', 'deepseek-chat'),
            'requires_api_key' => true,
            'api_key' => env('DEEPSEEK_API_KEY'),
            'description' => 'Modèle DeepSeek polyvalent et performant pour diverses tâches conversationnelles.',
            'cost_per_1k_tokens' => 0.00014,
            'max_context_length' => 32000,
            'default_config' => [
                'temperature' => 0.7,
                'max_tokens' => 1500,
                'top_p' => 0.95,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Models Configuration
    |--------------------------------------------------------------------------
    |
    | Cette section est gérée dynamiquement par AiConfigurationService
    | pour éviter les références circulaires dans la configuration
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Testing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration spécifique aux tests
    |
    */
    'testing' => [
        'ollama' => [
            'endpoint_url' => env('OLLAMA_TEST_ENDPOINT_URL', 'http://209.126.83.125:11434'),
            'model_identifier' => env('OLLAMA_TEST_MODEL', 'gemma2:2b'),
            'timeout' => env('OLLAMA_TEST_TIMEOUT', 60),
        ],
        'mock_endpoints' => [
            'inaccessible' => 'http://localhost:99999',
        ],
    ],
];
