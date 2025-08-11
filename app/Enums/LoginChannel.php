<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self EMAIL()
 * @method static self PHONE()
 */
class LoginChannel extends Enum
{
    public static function labels(): array
    {
        return [
            'EMAIL' => 'Email',
            'PHONE' => 'Téléphone',
        ];
    }

    public static function values(): array
    {
        return [
            'EMAIL' => 'email',
            'PHONE' => 'phone',
        ];
    }
}
