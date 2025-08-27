<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self COMMERCIAL()
 * @method static self SUPPORT()
 */
final class AgentType extends Enum
{
    protected static function values(): array
    {
        return [
            'COMMERCIAL' => 'commercial',
            'SUPPORT' => 'support',
        ];
    }

    protected static function labels(): array
    {
        return [
            'COMMERCIAL' => 'Agent Commercial',
            'SUPPORT' => 'Agent Support Client',
        ];
    }

    public function getDescription(): string
    {
        return match ($this->value) {
            'commercial' => 'Expert en vente consultative et développement commercial',
            'support' => 'Spécialiste du support client et résolution de problèmes',
            default => 'Agent IA polyvalent',
        };
    }

    public function getIcon(): string
    {
        return match ($this->value) {
            'commercial' => 'currency-dollar',
            'support' => 'chat-bubble-left-right',
            default => 'cpu-chip',
        };
    }
}
