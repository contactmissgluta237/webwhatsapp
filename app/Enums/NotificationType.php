<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self ORDER_CREATED()
 * @method static self ORDER_CONFIRMED()
 * @method static self ORDER_PROCESSING()
 * @method static self ORDER_DELIVERED()
 * @method static self ORDER_CANCELLED()
 * @method static self ORDER_MODIFIED()
 */
class NotificationType extends Enum
{
    /**
     * @return string[]
     */
    public static function labels(): array
    {
        return [
            'ORDER_CREATED' => 'Nouvelle commande créée',
            'ORDER_CONFIRMED' => 'Commande confirmée',
            'ORDER_PROCESSING' => 'Commande en cours de traitement',
            'ORDER_DELIVERED' => 'Commande livrée',
            'ORDER_CANCELLED' => 'Commande annulée',
            'ORDER_MODIFIED' => 'Commande modifiée',
        ];
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return [
            'ORDER_CREATED' => 'order_created',
            'ORDER_CONFIRMED' => 'order_confirmed',
            'ORDER_PROCESSING' => 'order_processing',
            'ORDER_DELIVERED' => 'order_delivered',
            'ORDER_CANCELLED' => 'order_cancelled',
            'ORDER_MODIFIED' => 'order_modified',
        ];
    }

    /**
     * Get icon for notification type
     */
    public function getIcon(): string
    {
        return match ($this->value) {
            'order_created' => 'la-shopping-cart',
            'order_confirmed' => 'la-check-circle',
            'order_processing' => 'la-clock-o',
            'order_delivered' => 'la-truck',
            'order_cancelled' => 'la-times-circle',
            'order_modified' => 'la-edit',
        };
    }

    /**
     * Get badge class for notification type
     */
    public function getBadgeClass(): string
    {
        return match ($this->value) {
            'order_created' => 'text-white icon-bg-circle bg-cyan ',
            'order_confirmed' => 'bg-light-info text-light-info',
            'order_processing' => 'bg-light-warning text-light-warning',
            'order_delivered' => 'bg-light-success text-light-success',
            'order_cancelled' => 'bg-light-danger text-light-danger',
            'order_modified' => 'bg-light-warning text-light-warning',
        };
    }

    /**
     * Get color class for notification type
     */
    public function getColorClass(): string
    {
        return match ($this->value) {
            'order_created' => 'text-primary',
            'order_confirmed' => 'text-info',
            'order_processing' => 'text-warning',
            'order_delivered' => 'text-success',
            'order_cancelled' => 'text-danger',
            'order_modified' => 'text-warning',
        };
    }
}
