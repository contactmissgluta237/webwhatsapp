<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self ACTIVE()
 * @method static self INACTIVE()
 * @method static self USED()
 * @method static self EXPIRED()
 */
class CouponStatus extends Enum
{
    public static function values(): array
    {
        return [
            'ACTIVE' => 'active',
            'INACTIVE' => 'inactive',
            'USED' => 'used',
            'EXPIRED' => 'expired',
        ];
    }

    public static function labels(): array
    {
        return [
            'ACTIVE' => 'Actif',
            'INACTIVE' => 'Inactif',
            'USED' => 'Utilisé',
            'EXPIRED' => 'Expiré',
        ];
    }

    public function label(): string
    {
        return match ($this->value) {
            'active' => 'Actif',
            'inactive' => 'Inactif',
            'expired' => 'Expiré',
            'used' => 'Utilisé',
            default => 'Inconnu',
        };
    }

    public function badge(): string
    {
        return match ($this->value) {
            'active' => 'bg-success',
            'inactive' => 'bg-secondary',
            'used' => 'bg-warning',
            'expired' => 'bg-danger',
            default => 'bg-secondary',
        };
    }
}
