<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self MOBILE_MONEY()
 * @method static self ORANGE_MONEY()
 * @method static self BANK_CARD()
 * @method static self CASH()
 */
class PaymentMethod extends Enum
{
    /**
     * @return string[]
     */
    public static function values(): array
    {
        return [
            'MOBILE_MONEY' => 'mobile_money',
            'ORANGE_MONEY' => 'orange_money',
            'BANK_CARD' => 'bank_card',
            'CASH' => 'cash',
        ];
    }

    public static function labels(): array
    {
        return [
            'MOBILE_MONEY' => 'Mobile Money',
            'ORANGE_MONEY' => 'Orange Money',
            'BANK_CARD' => 'Carte Bancaire',
            'CASH' => 'Espèces',
        ];
    }

    public function icon(): string
    {
        return match ($this->value) {
            'mobile_money' => 'fas fa-mobile-alt',
            'orange_money' => 'fas fa-mobile-alt',
            'bank_card' => 'fas fa-credit-card',
            'cash' => 'fas fa-money-bill-wave',
            default => 'fas fa-payment',
        };
    }

    public function badge(): string
    {
        return match ($this->value) {
            'mobile_money', 'orange_money' => 'success',
            'bank_card' => 'primary',
            'cash' => 'warning',
            default => 'secondary',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            PaymentMethod::MOBILE_MONEY() => 'MTN',
            PaymentMethod::ORANGE_MONEY() => 'OM',
            PaymentMethod::BANK_CARD() => 'CARD',
            default => 'TRX',
        };
    }
}
