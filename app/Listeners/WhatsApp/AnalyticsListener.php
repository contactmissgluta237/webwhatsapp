<?php

declare(strict_types=1);

namespace App\Listeners\WhatsApp;

use App\Events\WhatsApp\MessageProcessedEvent;
use App\Listeners\BaseListener;
use Illuminate\Support\Facades\Log;

/**
 * Listener responsible for tracking analytics and metrics
 *
 * TODO: This will be implemented when analytics system is ready.
 * Will handle:
 * - Message volume tracking
 * - Response time metrics
 * - User engagement statistics
 * - AI model performance tracking
 * - Error rate monitoring
 */
final class AnalyticsListener extends BaseListener
{
    /**
     * Extracts unique identifiers for MessageProcessedEvent
     */
    protected function getEventIdentifiers($event): array
    {
        return [
            'account_id' => $event->account->id,
            'message_id' => $event->incomingMessage->id,
            'session_id' => $event->getSessionId(),
        ];
    }

    /**
     * Processes the event for analytics
     */
    protected function handleEvent($event): void
    {
        Log::debug('[ANALYTICS] Message processed for analytics tracking', [
            'session_id' => $event->getSessionId(),
            'from_phone' => $event->getFromPhone(),
            'ai_success' => $event->wasSuccessful(),
        ]);

        // Logic to handle analytics
        // - Log error rates and types
        // - Generate usage reports
        // - Send data to analytics platforms (Google Analytics, etc.)
    }
}
