<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Response Delays - Configuration Tests
    |--------------------------------------------------------------------------
    |
    | Délais ultra-courts pour les tests automatisés afin d'accélérer
    | l'exécution des suites de tests.
    |
    */

    'response_delays' => [
        'min' => 1, // Tests : 1 seconde minimum
        'max' => 2, // Tests : 2 secondes maximum
    ],
];
