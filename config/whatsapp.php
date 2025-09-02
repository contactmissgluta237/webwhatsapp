<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration globale pour les fonctionnalités WhatsApp
    |
    */

    'bridge' => [
        'base_url' => env('WHATSAPP_BRIDGE_URL', 'http://localhost:3000'),
        'timeout' => env('WHATSAPP_BRIDGE_TIMEOUT', 30),
        'api_token' => env('WHATSAPP_API_TOKEN'),
        'endpoints' => [
            'send_message' => '/api/bridge/send-message',
            'session_status' => '/api/bridge/session/{sessionId}/status',
            'create_session' => '/api/sessions',
            'get_session_status' => '/api/sessions/{sessionId}/status',
            'get_qr_code' => '/api/sessions/{sessionId}/qr',
            'destroy_session' => '/api/sessions/{sessionId}/destroy',
            'send_media' => '/api/bridge/send-media',
        ],
    ],

    'products' => [
        // Nombre maximum de produits pouvant être liés à un agent IA
        'max_linked_per_agent' => 10,

        // Nombre maximum de produits pouvant être envoyés en une fois
        'max_sent_per_message' => 10,

        // Délai entre l'envoi de chaque produit (en secondes)
        'send_delay_seconds' => 3,
    ],

    'ai' => [
        // Force la réponse JSON de l'IA
        'force_json_response' => true,

        // Timeout pour les réponses IA
        'response_timeout' => 60,

        // Limites de caractères
        'limits' => [
            // Limite fixe du prompt agent (tous utilisateurs)
            'agent_prompt_max_length' => env('WHATSAPP_AGENT_PROMPT_LIMIT', 3000),

            // Limite par défaut du contexte (utilisateurs sans abonnement)
            'context_default_max_length' => env('WHATSAPP_CONTEXT_DEFAULT_LIMIT', 3000),
        ],

        // Configuration de l'amélioration de prompts
        'enhancement' => [
            // Température pour l'amélioration (0-1, plus faible = plus déterministe)
            'temperature' => env('WHATSAPP_ENHANCEMENT_TEMPERATURE', 0.3),

            // Limite de tokens/caractères pour les prompts améliorés
            'max_tokens' => env('WHATSAPP_ENHANCEMENT_MAX_TOKENS', 4000),

            // Amélioration de ratio - seuil pour considérer qu'une amélioration est réussie
            'min_improvement_ratio' => env('WHATSAPP_ENHANCEMENT_MIN_RATIO', 0.8),
        ],
    ],

    'messaging' => [
        // Délai entre les messages pour éviter le spam
        'anti_spam_delay_ms' => 3000,

        // Taille maximale des messages
        'max_message_length' => 4096,
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Billing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour la facturation des messages WhatsApp
    |
    */

    'billing' => [
        // Coûts des différents types de messages (en USD)
        'costs' => [
            // Coût d'un message de réponse IA
            'ai_message' => env('WHATSAPP_COST_AI_MESSAGE', 0.002),

            // Coût d'un message texte pour un produit
            'product_message' => env('WHATSAPP_COST_PRODUCT_MESSAGE', 0.001),

            // Coût par média (image, vidéo, etc.)
            'media' => env('WHATSAPP_COST_MEDIA', 0.001),
        ],

        // Pourcentage du quota restant pour déclencher une alerte
        'alert_threshold_percentage' => env('WHATSAPP_ALERT_THRESHOLD', 20),
    ],
];
