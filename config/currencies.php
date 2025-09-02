<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mapping Pays → Devise par défaut
    |--------------------------------------------------------------------------
    */

    'country_currency_mapping' => [
        // Afrique de l'Ouest (Zone CFA BCEAO)
        'BF' => 'XOF', // Burkina Faso
        'BJ' => 'XOF', // Bénin
        'CI' => 'XOF', // Côte d'Ivoire
        'GW' => 'XOF', // Guinée-Bissau
        'ML' => 'XOF', // Mali
        'NE' => 'XOF', // Niger
        'SN' => 'XOF', // Sénégal
        'TG' => 'XOF', // Togo

        // Afrique Centrale (Zone CFA BEAC)
        'CM' => 'XAF', // Cameroun
        'CF' => 'XAF', // République Centrafricaine
        'TD' => 'XAF', // Tchad
        'CG' => 'XAF', // Congo
        'GQ' => 'XAF', // Guinée Équatoriale
        'GA' => 'XAF', // Gabon

        // Autres pays africains principaux
        'DZ' => 'DZD', // Algérie
        'MA' => 'MAD', // Maroc
        'TN' => 'TND', // Tunisie
        'EG' => 'EGP', // Égypte
        'NG' => 'NGN', // Nigeria
        'GH' => 'GHS', // Ghana
        'KE' => 'KES', // Kenya
        'ZA' => 'ZAR', // Afrique du Sud

        // Pays occidentaux
        'FR' => 'EUR', // France
        'DE' => 'EUR', // Allemagne
        'IT' => 'EUR', // Italie
        'ES' => 'EUR', // Espagne
        'US' => 'USD', // États-Unis
        'CA' => 'CAD', // Canada
        'GB' => 'GBP', // Royaume-Uni
        'CH' => 'CHF', // Suisse
    ],

    /*
    |--------------------------------------------------------------------------
    | Informations des devises
    |--------------------------------------------------------------------------
    */

    'currencies' => [
        'XOF' => [
            'name' => 'Franc CFA (BCEAO)',
            'symbol' => 'F CFA',
            'code' => 'XOF',
            'decimals' => 0,
        ],
        'XAF' => [
            'name' => 'Franc CFA (BEAC)',
            'symbol' => 'XAF',
            'code' => 'XAF',
            'decimals' => 0,
        ],
        'EUR' => [
            'name' => 'Euro',
            'symbol' => '€',
            'code' => 'EUR',
            'decimals' => 2,
        ],
        'USD' => [
            'name' => 'Dollar américain',
            'symbol' => '$',
            'code' => 'USD',
            'decimals' => 2,
        ],
        'GBP' => [
            'name' => 'Livre sterling',
            'symbol' => '£',
            'code' => 'GBP',
            'decimals' => 2,
        ],
        'CAD' => [
            'name' => 'Dollar canadien',
            'symbol' => 'C$',
            'code' => 'CAD',
            'decimals' => 2,
        ],
        'CHF' => [
            'name' => 'Franc suisse',
            'symbol' => 'CHF',
            'code' => 'CHF',
            'decimals' => 2,
        ],
        'NGN' => [
            'name' => 'Naira',
            'symbol' => '₦',
            'code' => 'NGN',
            'decimals' => 2,
        ],
        'GHS' => [
            'name' => 'Cedi ghanéen',
            'symbol' => '₵',
            'code' => 'GHS',
            'decimals' => 2,
        ],
        'ZAR' => [
            'name' => 'Rand',
            'symbol' => 'R',
            'code' => 'ZAR',
            'decimals' => 2,
        ],
        'MAD' => [
            'name' => 'Dirham marocain',
            'symbol' => 'DH',
            'code' => 'MAD',
            'decimals' => 2,
        ],
        'DZD' => [
            'name' => 'Dinar algérien',
            'symbol' => 'DA',
            'code' => 'DZD',
            'decimals' => 2,
        ],
        'TND' => [
            'name' => 'Dinar tunisien',
            'symbol' => 'TND',
            'code' => 'TND',
            'decimals' => 3,
        ],
        'EGP' => [
            'name' => 'Livre égyptienne',
            'symbol' => 'LE',
            'code' => 'EGP',
            'decimals' => 2,
        ],
        'KES' => [
            'name' => 'Shilling kenyan',
            'symbol' => 'KSh',
            'code' => 'KES',
            'decimals' => 2,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Devise par défaut
    |--------------------------------------------------------------------------
    */

    'default_currency' => env('APP_DEFAULT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Configuration centralisée pour l'élimination des hardcoded currencies
    |--------------------------------------------------------------------------
    */

    // Devise par défaut de l'application
    'default' => env('APP_DEFAULT_CURRENCY', 'USD'),

    // Symboles de devises optimisés
    'symbols' => [
        'USD' => '$',
        'XAF' => 'FCFA',
        'EUR' => '€',
        'XOF' => 'F CFA',
        'GBP' => '£',
        'CAD' => 'C$',
        'CHF' => 'CHF',
        'NGN' => '₦',
        'GHS' => '₵',
        'ZAR' => 'R',
        'MAD' => 'DH',
        'DZD' => 'DA',
        'TND' => 'TND',
        'EGP' => 'LE',
        'KES' => 'KSh',
    ],

    // Configuration de formatage par devise
    'formatting' => [
        'USD' => [
            'decimals' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => '',
            'position' => 'after', // $ après le montant
        ],
        'XAF' => [
            'decimals' => 0,
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
            'position' => 'after', // FCFA après le montant
        ],
        'EUR' => [
            'decimals' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ' ',
            'position' => 'after', // € après le montant
        ],
        'XOF' => [
            'decimals' => 0,
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
            'position' => 'after',
        ],
    ],

    // Taux de conversion (à déplacer vers une source dynamique plus tard)
    'exchange_rates' => [
        'USD_TO_XAF' => env('EXCHANGE_RATE_USD_TO_XAF', 650),
        'USD_TO_EUR' => env('EXCHANGE_RATE_USD_TO_EUR', 0.92),
        'USD_TO_XOF' => env('EXCHANGE_RATE_USD_TO_XOF', 650),
    ],

    // Devises autorisées dans l'application
    'allowed' => ['USD', 'XAF', 'EUR', 'XOF'],

    /*
    |--------------------------------------------------------------------------
    | Mappings des gateways de paiement
    |--------------------------------------------------------------------------
    */

    'gateway_mappings' => [
        'mycoolpay' => [
            'mobile_money' => 'XAF',
            'orange_money' => 'XAF',
            'bank_card' => 'EUR',
        ],
    ],
];
