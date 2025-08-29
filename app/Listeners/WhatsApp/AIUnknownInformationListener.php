<?php

declare(strict_types=1);

namespace App\Listeners\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppAIStructuredResponseDTO;
use App\Listeners\BaseListener;
use App\Notifications\WhatsApp\AIUnknownInformationNotification;
use Illuminate\Support\Facades\Log;

final class AIUnknownInformationListener extends BaseListener
{
    protected function getEventIdentifiers($event): array
    {
        return [
            'account_id' => $event->account->id,
            'session_id' => $event->getSessionId(),
            'from_phone' => $event->getFromPhone(),
        ];
    }

    protected function handleEvent($event): void
    {
        if (! $event->wasSuccessful()) {
            return;
        }

        // Parse the structured response from the AI response
        if (! $event->aiResponse->aiDetails) {
            Log::warning('[AI_UNKNOWN] No AI details in response', [
                'account_id' => $event->account->id,
                'session_id' => $event->getSessionId(),
            ]);

            return;
        }

        try {
            $structuredResponse = WhatsAppAIStructuredResponseDTO::fromAIResponse($event->aiResponse->aiDetails);
        } catch (\InvalidArgumentException $e) {
            Log::warning('[AI_UNKNOWN] Failed to parse AI response', [
                'account_id' => $event->account->id,
                'session_id' => $event->getSessionId(),
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if (! $structuredResponse->unknownInformation) {
            return;
        }

        $account = $event->account;
        $settings = $account->settings;

        // Vérifier si des notifications sont configurées
        if (! $settings || ! $settings->hasNotificationsEnabled()) {
            Log::info('[AI_UNKNOWN] No notifications configured for account', [
                'account_id' => $account->id,
                'session_id' => $event->getSessionId(),
            ]);

            return;
        }

        try {
            $notification = new AIUnknownInformationNotification(
                $account,
                $event->incomingMessage,
                $structuredResponse->message
            );

            // Send notification using Laravel's notification system
            // The notification will determine which channels to use based on settings
            $account->notify($notification);

            Log::info('[AI_UNKNOWN] Notifications processed successfully', [
                'account_id' => $account->id,
                'session_id' => $event->getSessionId(),
                'customer_phone' => $event->getFromPhone(),
                'channels' => $notification->via($account),
            ]);

        } catch (\Exception $e) {
            Log::error('[AI_UNKNOWN] Failed to send notifications', [
                'account_id' => $account->id,
                'session_id' => $event->getSessionId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
