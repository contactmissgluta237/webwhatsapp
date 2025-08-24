<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Message Pricing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration des coûts des messages WhatsApp AI
    | Ces valeurs sont utilisées pour calculer le coût des messages
    | et suivre la consommation des utilisateurs
    |
    */

    // Coût de base par message en XAF
    'message_base_cost_xaf' => env('MESSAGE_BASE_COST_XAF', 10),

    // Coût par média envoyé (images dans les produits)
    'media_cost_multiplier' => env('MEDIA_COST_MULTIPLIER', 1),

    /*
    |--------------------------------------------------------------------------
    | Usage Limits & Warnings
    |--------------------------------------------------------------------------
    |
    | Seuils pour les alertes de consommation
    |
    */

    // Pourcentage à partir duquel alerter l'utilisateur
    'usage_warning_threshold' => 80,

    // Pourcentage à partir duquel l'utilisateur est proche de la limite
    'usage_critical_threshold' => 95,

    /*
    |--------------------------------------------------------------------------
    | Package Features Pricing
    |--------------------------------------------------------------------------
    |
    | Coûts additionnels pour certaines fonctionnalités premium
    |
    */

    'features' => [
        'priority_support_cost' => 0, // Gratuit pour l'instant
        'api_access_cost' => 0,       // Gratuit pour l'instant
        'weekly_reports_cost' => 0,   // Gratuit pour l'instant
    ],

    /*
    |--------------------------------------------------------------------------
    | Trial Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration spécifique au package trial
    |
    */

    'trial' => [
        'duration_days' => 7,
        'messages_limit' => 50,
        'can_be_used_once' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Overage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour les dépassements payants
    | Quand l'utilisateur dépasse sa limite de messages mais a de l'argent
    |
    */

    'overage' => [
        // Permettre les dépassements payants automatiques
        'enabled' => env('OVERAGE_ENABLED', true),
        
        // Coût par message de dépassement (même tarif que base)
        'cost_per_message_xaf' => env('OVERAGE_COST_XAF', 10),
        
        // Minimum de solde à garder dans le wallet (sécurité)
        'minimum_wallet_balance' => env('MINIMUM_WALLET_BALANCE', 0),
        
        // Maximum de messages de dépassement autorisés par cycle
        'max_overage_messages_per_cycle' => env('MAX_OVERAGE_MESSAGES', 1000),
    ],

];