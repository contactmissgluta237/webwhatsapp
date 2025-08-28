<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self PERCENTAGE()
 * @method static self FIXED_AMOUNT()
 */
class CouponType extends Enum
{
    public static function values(): array
    {
        return [
            'PERCENTAGE' => 'percentage',
            'FIXED_AMOUNT' => 'fixed_amount',
        ];
    }

    public static function labels(): array
    {
        return [
            'PERCENTAGE' => 'Pourcentage',
            'FIXED_AMOUNT' => 'Montant Fixe',
        ];
    }

    public function label(): string
    {
        return static::labels()[$this->name];
    }
}
