<?php

namespace App\Enums;

// Importing the Spatie Enum package to define and manage enums in a Laravel application.
use Spatie\Enum\Laravel\Enum;

/**
 * @method static self AUTOMATIC()
 * @method static self MANUAL()
 */
class TransactionMode extends Enum
{
    /**
     * @return string[]
     */
    public static function values(): array
    {
        return [
            'AUTOMATIC' => 'automatic',
            'MANUAL' => 'manual',
        ];
    }

    public static function labels(): array
    {
        return [
            'AUTOMATIC' => 'Automatique',
            'MANUAL' => 'Manuel',
        ];
    }

    public function icon(): string
    {
        return match ($this->value) {
            'automatic' => 'fas fa-robot',
            'manual' => 'fas fa-hand-paper',
            default => 'fas fa-cog',
        };
    }

    public function badge(): string
    {
        return match ($this->value) {
            'automatic' => 'primary',
            'manual' => 'info',
            default => 'secondary',
        };
    }
}
