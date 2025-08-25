<?php

namespace App\Services\Payment\Gateways\MyCoolPay\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self SUCCESS()
 * @method static self FAILED()
 * @method static self CANCELED()
 */
final class MyCoolPayTransactionStatus extends Enum
{
    protected static function values(): array
    {
        return [
            'SUCCESS' => 'SUCCESS',
            'FAILED' => 'FAILED',
            'CANCELED' => 'CANCELED',
        ];
    }
}
