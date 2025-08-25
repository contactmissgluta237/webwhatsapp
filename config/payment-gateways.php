<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Mappings
    |--------------------------------------------------------------------------
    |
    | This configuration maps countries to their respective payment gateways.
    | Each country code is mapped to a specific gateway class.
    |
    */

    'mappings' => [
        'CM' => \App\Services\Payment\Gateways\MyCoolPayGateway::class,
        // Future mappings:
        // 'SN' => \App\Services\Payment\Gateways\FlutterwaveGateway::class,
        // 'CI' => \App\Services\Payment\Gateways\WaveGateway::class,
    ],

];
