<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self OPEN()
 * @method static self REPLIED()
 * @method static self CLOSED()
 * @method static self RESOLVED()
 */
class TicketStatus extends Enum
{
    /**
     * @return string[]
     */
    public static function labels(): array
    {
        return [
            'OPEN' => __('tickets.status_open'),
            'REPLIED' => __('tickets.status_replied'),
            'CLOSED' => __('tickets.status_closed'),
            'RESOLVED' => __('tickets.status_resolved'),
        ];
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return [
            'OPEN' => 'open',
            'REPLIED' => 'replied',
            'CLOSED' => 'closed',
            'RESOLVED' => 'resolved',
        ];
    }

    public function badge(): string
    {
        return match ($this->value) {
            'open' => 'info',
            'replied' => 'success',
            'closed' => 'danger',
            'resolved' => 'primary',
            default => 'secondary',
        };
    }
}
