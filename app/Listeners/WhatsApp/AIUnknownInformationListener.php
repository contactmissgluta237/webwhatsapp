<?php

declare(strict_types=1);

namespace App\Listeners\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppAIStructuredResponseDTO;
use App\Events\WhatsApp\MessageProcessedEvent;
use App\Listeners\BaseListener;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppAccountSetting;
use App\Notifications\WhatsApp\AIUnknownInformationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

final class AIUnknownInformationListener extends BaseListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected function getEventIdentifiers($event): array
    {
        return [
            'account_id' => $event->account->id,
            'session_id' => $event->getSessionId(),
            'message_id' => $event->incomingMessage->id,
        ];
    }

    /**
     * @param  MessageProcessedEvent  $event
     */
    protected function handleEvent($event): void
    {
        if (! $this->shouldProcessEvent($event)) {
            return;
        }

        $structuredResponse = $this->parseAIResponse($event);
        if (! $structuredResponse?->unknownInformation) {
            return;
        }

        $this->sendNotifications($event, $structuredResponse->message);
    }

    private function shouldProcessEvent(MessageProcessedEvent $event): bool
    {
        if (! $event->wasSuccessful()) {
            Log::debug('[AI_UNKNOWN] Skipping unsuccessful event', [
                'account_id' => $event->account->id,
                'session_id' => $event->getSessionId(),
            ]);

            return false;
        }

        if (! $event->aiResponse->aiDetails) {
            Log::warning('[AI_UNKNOWN] No AI details in response', [
                'account_id' => $event->account->id,
                'session_id' => $event->getSessionId(),
            ]);

            return false;
        }

        return true;
    }

    private function parseAIResponse(MessageProcessedEvent $event): ?WhatsAppAIStructuredResponseDTO
    {
        try {
            return WhatsAppAIStructuredResponseDTO::fromAIResponse($event->aiResponse->aiDetails);
        } catch (InvalidArgumentException $e) {
            Log::warning('[AI_UNKNOWN] Failed to parse AI response', [
                'account_id' => $event->account->id,
                'session_id' => $event->getSessionId(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function sendNotifications(MessageProcessedEvent $event, string $aiMessage): void
    {
        $account = $event->account;
        $settings = $account->settings;

        if (! $this->hasNotificationsEnabled($settings)) {
            Log::info('[AI_UNKNOWN] No notifications configured', [
                'account_id' => $account->id,
                'session_id' => $event->getSessionId(),
            ]);

            return;
        }

        try {
            $this->sendStandardNotifications($account, $event, $aiMessage);

            Log::info('[AI_UNKNOWN] All notifications sent successfully', [
                'account_id' => $account->id,
                'session_id' => $event->getSessionId(),
                'customer_phone' => $event->getFromPhone(),
            ]);

        } catch (\Throwable $e) {
            Log::error('[AI_UNKNOWN] Notification sending failed', [
                'account_id' => $account->id,
                'session_id' => $event->getSessionId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function hasNotificationsEnabled(?WhatsAppAccountSetting $settings): bool
    {
        return $settings && $settings->hasNotificationsEnabled();
    }

    private function sendStandardNotifications(
        WhatsAppAccount $account,
        MessageProcessedEvent $event,
        string $aiMessage
    ): void {
        Log::info('[AI_UNKNOWN] Creating notification', [
            'account_id' => $account->id,
            'session_id' => $event->getSessionId(),
        ]);

        $notification = new AIUnknownInformationNotification(
            $account,
            $event->incomingMessage,
            $aiMessage
        );

        Log::info('[AI_UNKNOWN] Sending notification via Laravel notification system', [
            'account_id' => $account->id,
            'notification_class' => get_class($notification),
        ]);

        $account->notify($notification);

        Log::info('[AI_UNKNOWN] Notification sent successfully', [
            'account_id' => $account->id,
        ]);
    }
}
