<?php

declare(strict_types=1);

namespace App\Listeners\WhatsApp;

use App\Events\WhatsApp\MessageProcessedEvent;
use Illuminate\Support\Facades\Log;

/**
 * Listener responsible for tracking message usage for billing/package limits
 * 
 * TODO: This will be implemented when the package/billing system is ready.
 * Will handle:
 * - Decrementing user's monthly message quota
 * - Tracking usage statistics per package
 * - Blocking users who exceed their limits
 * - Logging billing events
 */
final class BillingCounterListener
{
    /**
     * Handle the event and update billing counters
     */
    public function handle(MessageProcessedEvent $event): void
    {
        Log::debug('[BILLING_COUNTER] Message processed for billing tracking', [
            'session_id' => $event->getSessionId(),
            'from_phone' => $event->getFromPhone(),
            'ai_success' => $event->wasSuccessful(),
        ]);

        // TODO: Implement billing logic when package system is ready
        // This could include:
        // - Get user's current package/subscription
        // - Check remaining quota for the month
        // - Decrement quota if message was successful
        // - Log usage statistics
        // - Send notifications if approaching limits
        // - Block future messages if quota exceeded
    }
}