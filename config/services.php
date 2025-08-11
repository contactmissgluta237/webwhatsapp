<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

    'google' => [
        'maps' => [
            'api_key' => env('GOOGLE_MAPS_API_KEY'),
        ],
    ],
    'mycoolpay' => [
        'public_key' => env('MYCOOLPAY_PUBLIC_KEY'),
        'private_key' => env('MYCOOLPAY_PRIVATE_KEY'),
    ],

    'whatsapp_bridge' => [
        'url' => 'http://'.env('WHATSAPP_BRIDGE_HOST', 'localhost').':'.env('WHATSAPP_BRIDGE_PORT', '3000'),
        'docker_url' => 'http://'.env('WHATSAPP_BRIDGE_DOCKER_HOST', 'whatsapp-bridge').':'.env('WHATSAPP_BRIDGE_DOCKER_PORT', '3000'),
        'api_token' => env('WHATSAPP_API_TOKEN'),
    ],

    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://localhost:11434'),
        'default_model' => env('OLLAMA_DEFAULT_MODEL', 'llama2:7b-chat'),
    ],

];
