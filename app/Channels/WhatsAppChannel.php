<?php

declare(strict_types=1);

namespace App\Channels;

use App\Services\WhatsApp\WhatsAppNotificationHandler;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

final class WhatsAppChannel
{
    public function __construct(
        private readonly WhatsAppNotificationHandler $handler
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        Log::info('[WHATSAPP_CHANNEL] Channel called', [
            'notification_class' => get_class($notification),
            'notifiable_class' => get_class($notifiable),
        ]);

        if (! method_exists($notification, 'toWhatsApp')) {
            Log::warning('[WHATSAPP_CHANNEL] Notification missing toWhatsApp method', [
                'notification_class' => get_class($notification),
            ]);

            return;
        }

        $whatsappData = $notification->toWhatsApp($notifiable);

        Log::info('[WHATSAPP_CHANNEL] WhatsApp data retrieved', [
            'data' => $whatsappData,
        ]);

        if (! $whatsappData || ! $this->hasRequiredFields($whatsappData)) {
            Log::warning('[WHATSAPP_CHANNEL] Invalid WhatsApp data', [
                'data' => $whatsappData,
            ]);

            return;
        }

        Log::info('[WHATSAPP_CHANNEL] Calling handler sendNotification', [
            'session_id' => $whatsappData['session_id'],
            'to' => $whatsappData['to'],
            'message' => substr($whatsappData['message'], 0, 100).'...',
        ]);

        $this->handler->sendNotification(
            $whatsappData['session_id'],
            $whatsappData['to'],
            $whatsappData['message']
        );
    }

    private function hasRequiredFields(array $data): bool
    {
        return isset($data['session_id'], $data['to'], $data['message']);
    }
}
