<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self SUCCESS()
 * @method static self ERROR()
 * @method static self WARNING()
 * @method static self INFO()
 */
final class FlashMessageType extends Enum
{
    protected static function values(): array
    {
        return [
            'SUCCESS' => 'success',
            'ERROR' => 'error',
            'WARNING' => 'warning',
            'INFO' => 'info',
        ];
    }

    protected static function labels(): array
    {
        return [
            'success' => 'Succès',
            'error' => 'Erreur',
            'warning' => 'Avertissement',
            'info' => 'Information',
        ];
    }

    public function getBootstrapClass(): string
    {
        return match ($this->value) {
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info',
        };
    }

    public function getToastrMethod(): string
    {
        return match ($this->value) {
            'success' => 'success',
            'error' => 'error',
            'warning' => 'warning',
            'info' => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this->value) {
            'success' => 'check-circle',
            'error' => 'x-circle',
            'warning' => 'exclamation-triangle',
            'info' => 'info-circle',
        };
    }
}
