<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self SYSTEM()
 * @method static self USER()
 * @method static self ASSISTANT()
 */
final class SimulatorMessageType extends Enum
{
    protected static function values(): array
    {
        return [
            'SYSTEM' => 'system',
            'USER' => 'user',
            'ASSISTANT' => 'assistant',
        ];
    }

    protected static function labels(): array
    {
        return [
            'system' => 'Système',
            'user' => 'Utilisateur',
            'assistant' => 'Assistant IA',
        ];
    }

    public function getIcon(): string
    {
        return match ($this->value) {
            'system' => 'cog',
            'user' => 'user',
            'assistant' => 'robot',
        };
    }

    public function getCssClass(): string
    {
        return match ($this->value) {
            'system' => 'text-muted',
            'user' => 'text-primary',
            'assistant' => 'text-success',
        };
    }
}
