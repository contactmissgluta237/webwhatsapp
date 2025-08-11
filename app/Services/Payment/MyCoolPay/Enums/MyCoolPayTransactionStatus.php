<?php

namespace App\Services\Payment\MyCoolPay\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self PENDING()
 * @method static self SUCCESS()
 * @method static self FAILED()
 * @method static self CANCELED()
 * @method static self INITIATED()
 * @method static self PAID()
 * @method static self ERROR()
 */
final class MyCoolPayTransactionStatus extends Enum
{
    protected static function values(): array
    {
        return [
            'PENDING' => 'pending',
            'SUCCESS' => 'success',
            'FAILED' => 'failed',
            'CANCELED' => 'canceled',
            'INITIATED' => 'initiated',
            'PAID' => 'paid',
            'ERROR' => 'error',
        ];
    }
}
