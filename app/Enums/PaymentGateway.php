<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self MYCOOLPAY()
 */
class PaymentGateway extends Enum
{
    /**
     * @return string[]
     */
    public static function values(): array
    {
        return [
            'MYCOOLPAY' => 'mycoolpay',
        ];
    }

    public static function labels(): array
    {
        return [
            'MYCOOLPAY' => 'MyCoolPay',
        ];
    }

    public function icon(): string
    {
        return match ($this->value) {
            'mycoolpay' => 'fas fa-mobile-alt',
            default => 'fas fa-credit-card',
        };
    }

    public function badge(): string
    {
        return match ($this->value) {
            'mycoolpay' => 'primary',
            default => 'secondary',
        };
    }
}
