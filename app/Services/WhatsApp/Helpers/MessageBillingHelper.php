<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Helpers;

use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;

final class MessageBillingHelper
{
    /**
     * Get total number of messages to debit from quota
     * AI message = 1, each product message = 1, each media = 1
     */
    public static function getNumberOfMessagesFromResponse(WhatsAppMessageResponseDTO $response): int
    {
        $totalMessages = 0;

        // AI response counts as 1 message
        if ($response->hasAiResponse && $response->aiResponse !== null) {
            $totalMessages += 1;
        }

        // Each product generates 1 message + count of its media
        foreach ($response->products as $product) {
            // Product message itself = 1
            $totalMessages += 1;

            // Each media = 1
            $totalMessages += count($product->mediaUrls);
        }

        return $totalMessages;
    }

    /**
     * Get total amount to bill in XAF based on response and configuration
     */
    public static function getAmountToBillFromResponse(WhatsAppMessageResponseDTO $response): float
    {
        $totalAmount = 0.0;

        // AI message cost
        if ($response->hasAiResponse && $response->aiResponse !== null) {
            $totalAmount += config('whatsapp.billing.costs.ai_message', 15);
        }

        // Products and media costs
        foreach ($response->products as $product) {
            // Product message cost
            $totalAmount += config('whatsapp.billing.costs.product_message', 10);

            // Media costs
            $mediaCount = count($product->mediaUrls);
            $totalAmount += $mediaCount * config('whatsapp.billing.costs.media', 5);
        }

        return $totalAmount;
    }
}
