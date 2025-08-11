<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self CUSTOMER()
 * @method static self ADMIN()
 */
class TicketSenderType extends Enum
{
    /**
     * @return string[]
     */
    public static function labels(): array
    {
        return [
            'CUSTOMER' => 'Client',
            'ADMIN' => 'Administrateur',
        ];
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return [
            'CUSTOMER' => 'customer',
            'ADMIN' => 'admin',
        ];
    }

    public function label(): string
    {
        return match ($this->value) {
            'customer' => 'Client',
            'admin' => 'Administrateur',
        };
    }
}
