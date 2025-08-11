<?php

// app/Enums/PaymentStatus.php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self PENDING()
 * @method static self COMPLETED()
 * @method static self FAILED()
 * @method static self CANCELLED()
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
        ];
    }
}
