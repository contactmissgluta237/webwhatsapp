<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Helpers;

use App\Models\UserProduct;
use Illuminate\Support\Collection;

class MessageCostHelper
{
    public static function calculateMessageCost(Collection $products): int
    {
        $baseCost = 1; // 1 message de base
        $mediaCost = 0;

        foreach ($products as $product) {
            if (method_exists($product, 'getMediaCollection')) {
                $mediaCost += $product->getMediaCollection('images')->count();
            }
        }

        return $baseCost + $mediaCost;
    }

    public static function calculateEstimatedCostInXAF(int $messageCost): float
    {
        $costPerMessage = config('pricing.message_base_cost_xaf', 10);
        return $messageCost * $costPerMessage;
    }

    public static function getBaseCostXAF(): float
    {
        return config('pricing.message_base_cost_xaf', 10);
    }

    public static function canAffordMessage(int $remainingMessages, int $requiredCost): bool
    {
        return $remainingMessages >= $requiredCost;
    }

    public static function canProcessMessage(\App\Models\User $user, int $messageCost): bool
    {
        $subscription = $user->activeSubscription;
        
        if (!$subscription) {
            return false;
        }

        $tracker = $subscription->getCurrentCycleTracker();
        
        if (!$tracker) {
            $tracker = $subscription->getOrCreateCurrentCycleTracker();
        }

        return $tracker->canProcessMessage($messageCost);
    }

    public static function getMediaCount(UserProduct $product): int
    {
        return $product->getMediaCollection('images')->count();
    }

    public static function getProductsMediaCount(Collection $products): int
    {
        return $products->sum(function ($product) {
            if (method_exists($product, 'getMediaCollection')) {
                return $product->getMediaCollection('images')->count();
            }
            return 0;
        });
    }

    public static function calculateDetailedCost(Collection $products): array
    {
        $baseCost = 1;
        $mediaCount = self::getProductsMediaCount($products);
        $totalCost = $baseCost + $mediaCount;

        return [
            'base_messages' => $baseCost,
            'media_messages' => $mediaCount,
            'total_cost' => $totalCost,
            'products_count' => $products->count(),
            'estimated_xaf' => self::calculateEstimatedCostInXAF($totalCost),
        ];
    }
}