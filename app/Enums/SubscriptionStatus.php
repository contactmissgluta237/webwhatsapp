<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self ACTIVE()
 * @method static self CANCELLED()
 * @method static self EXPIRED()
 * @method static self PENDING()
 * @method static self SUSPENDED()
 * @method static array values()
 * @method static array labels()
 */
final class SubscriptionStatus extends Enum
{
    public static function values(): array
    {
        return [
            'ACTIVE' => 'active',
            'CANCELLED' => 'cancelled',
            'EXPIRED' => 'expired',
            'PENDING' => 'pending',
            'SUSPENDED' => 'suspended',
        ];
    }

    public static function labels(): array
    {
        return [
            'active' => __('subscriptions.status_active'),
            'cancelled' => __('subscriptions.status_cancelled'),
            'expired' => __('subscriptions.status_expired'),
            'pending' => __('subscriptions.status_pending'),
            'suspended' => __('subscriptions.status_suspended'),
        ];
    }

    public function getBadgeClass(): string
    {
        return match ($this->value) {
            'active' => 'bg-success',
            'cancelled' => 'bg-secondary',
            'expired' => 'bg-danger',
            'pending' => 'bg-warning',
            'suspended' => 'bg-secondary',
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
