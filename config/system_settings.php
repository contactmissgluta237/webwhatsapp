<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System Settings Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the global settings for the application.
    |
    */

    'predefined_amounts' => [
        500,
        1000,
        2000,
        5000,
        10000,
        25000,
        50000,
    ],

    'fees' => [
        // fees in percentage
        'withdrawal' => 3,
        'recharge' => 2,
    ],

    'ai_messaging' => [
        // Cost per AI message in FCFA
        'cost_per_message' => 50,
    ],
];
