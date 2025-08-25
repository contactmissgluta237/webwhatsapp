<?php

namespace App\Services\Payment\Gateways\MyCoolPay\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self PAYLINK()
 * @method static self PAYOUT()
 * @method static self PAYIN()
 */
final class MyCoolPayTransactionType extends Enum
{
    protected static function values(): array
    {
        return [
            'PAYLINK' => 'PAYLINK',
            'PAYOUT' => 'PAYOUT',
            'PAYIN' => 'PAYIN',
        ];
    }
}
