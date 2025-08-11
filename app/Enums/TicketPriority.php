<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self LOW()
 * @method static self MEDIUM()
 * @method static self HIGH()
 * @method static self URGENT()
 */
class TicketPriority extends Enum
{
    /**
     * @return string[]
     */
    public static function labels(): array
    {
        return [
            'LOW' => __('tickets.priority_low'),
            'MEDIUM' => __('tickets.priority_medium'),
            'HIGH' => __('tickets.priority_high'),
            'URGENT' => __('tickets.priority_urgent'),
        ];
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return [
            'LOW' => 'low',
            'MEDIUM' => 'medium',
            'HIGH' => 'high',
            'URGENT' => 'urgent',
        ];
    }

    public function badge(): string
    {
        return match ($this->value) {
            'low' => 'info',
            'medium' => 'primary',
            'high' => 'warning',
            'urgent' => 'danger',
            default => 'secondary',
        };
    }
}
