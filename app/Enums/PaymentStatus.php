<?php

// app/Enums/PaymentStatus.php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self PENDING()
 * @method static self COMPLETED()
 * @method static self FAILED()
 * @method static self CANCELLED()
 * @method static self INITIATED()
 * @method static self PAID()
 */
class PaymentStatus extends Enum
{
    public static function values(): array
    {
        return [
            'PENDING' => 'pending',
            'COMPLETED' => 'completed',
            'FAILED' => 'failed',
            'CANCELLED' => 'cancelled',
            'INITIATED' => 'initiated',
            'PAID' => 'paid',
        ];
    }
}
