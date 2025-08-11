<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self CREDIT()
 * @method static self DEBIT()
 */
class TransactionType extends Enum
{
    /**
     * @return string[]
     */
    public static function values(): array
    {
        return [
            'CREDIT' => 'credit',
            'DEBIT' => 'debit',
        ];
    }

    public static function labels(): array
    {
        return [
            'CREDIT' => 'Crédit',
            'DEBIT' => 'Débit',
        ];
    }
}
