<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self ACTIVE()
 * @method static self CANCELLED()
 * @method static self EXPIRED()
 * @method static self PENDING()
 */
final class SubscriptionStatus extends Enum
{
    protected static function values(): array
    {
        return [
            'ACTIVE' => 'active',
            'CANCELLED' => 'cancelled',
            'EXPIRED' => 'expired',
            'PENDING' => 'pending',
        ];
    }

    protected static function labels(): array
    {
        return [
            'active' => 'Actif',
            'cancelled' => 'Annulé',
            'expired' => 'Expiré',
            'pending' => 'En attente',
        ];
    }

    public function getBadgeClass(): string
    {
        return match ($this->value) {
            'active' => 'bg-success',
            'cancelled' => 'bg-secondary',
            'expired' => 'bg-danger',
            'pending' => 'bg-warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this->value) {
            'active' => 'check-circle',
            'cancelled' => 'x-circle',
            'expired' => 'clock',
            'pending' => 'clock-pending',
        };
    }

    public function isActive(): bool
    {
        return $this->value === 'active';
    }

    public function isCancelled(): bool
    {
        return $this->value === 'cancelled';
    }

    public function isExpired(): bool
    {
        return $this->value === 'expired';
    }
}
