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

    'node_js' => [
        'base_url' => env('WHATSAPP_BRIDGE_URL', 'http://localhost:3000'),
        'timeout' => env('WHATSAPP_BRIDGE_TIMEOUT', 30),
        'api_token' => env('WHATSAPP_API_TOKEN'),
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
        // Coûts des différents types de messages (en XAF)
        'costs' => [
            // Coût d'un message de réponse IA
            'ai_message' => env('WHATSAPP_COST_AI_MESSAGE', 15),

            // Coût d'un message texte pour un produit
            'product_message' => env('WHATSAPP_COST_PRODUCT_MESSAGE', 10),

            // Coût par média (image, vidéo, etc.)
            'media' => env('WHATSAPP_COST_MEDIA', 5),
        ],

        // Pourcentage du quota restant pour déclencher une alerte
        'alert_threshold_percentage' => env('WHATSAPP_ALERT_THRESHOLD', 20),
    ],
];
