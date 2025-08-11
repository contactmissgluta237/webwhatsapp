<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self PENDING()
 * @method static self COMPLETED()
 * @method static self FAILED()
 * @method static self CANCELLED()
 */
class TransactionStatus extends Enum
{
    /**
     * @return string[]
     */
    public static function values(): array
    {
        return [
            'PENDING' => 'pending',
            'COMPLETED' => 'completed',
            'FAILED' => 'failed',
            'CANCELLED' => 'cancelled',
        ];
    }

    public static function labels(): array
    {
        return [
            'PENDING' => 'En attente',
            'COMPLETED' => 'Terminé',
            'FAILED' => 'Échoué',
            'CANCELLED' => 'Annulé',
        ];
    }

    public function icon(): string
    {
        return match ($this->value) {
            'pending' => 'fas fa-clock',
            'completed' => 'fas fa-check-circle',
            'failed' => 'fas fa-times-circle',
            'cancelled' => 'fas fa-ban',
            default => 'fas fa-question-circle',
        };
    }

    public function badge(): string
    {
        return match ($this->value) {
            'pending' => 'warning',
            'completed' => 'success',
            'failed' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }
}
