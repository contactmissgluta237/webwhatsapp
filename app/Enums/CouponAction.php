<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self ACTIVATE()
 * @method static self DEACTIVATE()
 */
final class CouponAction extends Enum
{
    public static function values(): array
    {
        return [
            'ACTIVATE' => 'activate',
            'DEACTIVATE' => 'deactivate',
        ];
    }

    public static function labels(): array
    {
        return [
            'ACTIVATE' => 'Activer',
            'DEACTIVATE' => 'Désactiver',
        ];
    }

    public function label(): string
    {
        return match ($this->value) {
            'activate' => 'Activer',
            'deactivate' => 'Désactiver',
            default => 'Inconnu',
        };
    }
}
