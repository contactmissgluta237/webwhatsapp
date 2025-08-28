<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self ACTIVE()
 * @method static self USED()
 * @method static self EXPIRED()
 */
class CouponStatus extends Enum
{
    public static function values(): array
    {
        return [
            'ACTIVE' => 'active',
            'USED' => 'used',
            'EXPIRED' => 'expired',
        ];
    }

    public static function labels(): array
    {
        return [
            'ACTIVE' => 'Actif',
            'USED' => 'Utilisé',
            'EXPIRED' => 'Expiré',
        ];
    }

    public function badge(): string
    {
        return match ($this->value) {
            'active' => 'success',
            'used' => 'info',
            'expired' => 'danger',
            default => 'secondary',
        };
    }
}
