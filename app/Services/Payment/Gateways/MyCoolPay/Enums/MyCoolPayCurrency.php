<?php

namespace App\Services\Payment\Gateways\MyCoolPay\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self XAF()
 * @method static self EUR()
 */
final class MyCoolPayCurrency extends Enum
{
    protected static function values(): array
    {
        return [
            'XAF' => 'XAF',
            'EUR' => 'EUR',
        ];
    }
}
