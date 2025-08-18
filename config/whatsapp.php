<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Response Delays
    |--------------------------------------------------------------------------
    |
    | Configuration des délais de réponse pour simuler un comportement humain
    | naturel dans les conversations WhatsApp automatisées.
    |
    */

    'response_delays' => [
        'min' => env('WHATSAPP_RESPONSE_DELAY_MIN', 45),  // Délai minimum en secondes
        'max' => env('WHATSAPP_RESPONSE_DELAY_MAX', 180), // Délai maximum en secondes
    ],
];
