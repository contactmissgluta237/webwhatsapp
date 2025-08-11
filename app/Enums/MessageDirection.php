<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self INBOUND()
 * @method static self OUTBOUND()
 */
final class MessageDirection extends Enum
{
    protected static function values(): array
    {
        return [
            'INBOUND' => 'inbound',
            'OUTBOUND' => 'outbound',
        ];
    }

    protected static function labels(): array
    {
        return [
            'INBOUND' => 'Entrant',
            'OUTBOUND' => 'Sortant',
        ];
    }

    public function getIcon(): string
    {
        return match ($this->value) {
            'inbound' => 'arrow-down-left',
            'outbound' => 'arrow-up-right',
            default => 'arrows-right-left',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this->value) {
            'inbound' => 'bg-blue-100 text-blue-800',
            'outbound' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
