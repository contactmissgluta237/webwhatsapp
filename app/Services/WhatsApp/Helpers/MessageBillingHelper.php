<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Helpers;

use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;

final class MessageBillingHelper
{
    /**
     * Calculate total messages to debit from subscription quota.
     * AI response = 1, each product = 1, each media = 1.
     */
    public static function getNumberOfMessagesFromResponse(WhatsAppMessageResponseDTO $response): int
    {
        $total = $response->hasAiResponse && $response->aiResponse !== null ? 1 : 0;

        foreach ($response->products as $product) {
            $total += 1 + count($product->mediaUrls);
        }

        return $total;
    }

    /**
     * Calculate total billing amount in XAF based on configuration costs.
     */
    public static function getAmountToBillFromResponse(WhatsAppMessageResponseDTO $response): float
    {
        $total = 0.0;

        if ($response->hasAiResponse && $response->aiResponse !== null) {
            $total += config('whatsapp.billing.costs.ai_message', 15);
        }

        foreach ($response->products as $product) {
            $total += config('whatsapp.billing.costs.product_message', 10);
            $total += count($product->mediaUrls) * config('whatsapp.billing.costs.media', 5);
        }

        return $total;
    }

    /**
     * Get the number of products in the response.
     */
    public static function getNumberOfProductsFromResponse(WhatsAppMessageResponseDTO $response): int
    {
        return count($response->products);
    }

    /**
     * Get total media count across all products.
     */
    public static function getMediaCountFromResponse(WhatsAppMessageResponseDTO $response): int
    {
        return array_sum(array_map(fn ($product) => count($product->mediaUrls), $response->products));
    }
}
