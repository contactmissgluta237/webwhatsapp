<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self RECHARGE()
 * @method static self WITHDRAWAL()
 */
class ExternalTransactionType extends Enum
{
    /**
     * @return string[]
     */
    public static function values(): array
    {
        return [
            'RECHARGE' => 'recharge',
            'WITHDRAWAL' => 'withdrawal',
        ];
    }

    public static function labels(): array
    {
        return [
            'RECHARGE' => 'Recharge',
            'WITHDRAWAL' => 'Retrait',
        ];
    }

    public function icon(): string
    {
        return match ($this->value) {
            'recharge' => 'fas fa-plus-circle',
            'withdrawal' => 'fas fa-minus-circle',
            default => 'fas fa-exchange-alt',
        };
    }

    public function badge(): string
    {
        return match ($this->value) {
            'recharge' => 'success',
            'withdrawal' => 'warning',
            default => 'secondary',
        };
    }
}
