<?php

declare(strict_types=1);

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class WhatsAppNotificationChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $whatsappData = $notification->toWhatsApp($notifiable);

        if (! $whatsappData || ! $this->hasRequiredFields($whatsappData)) {
            return;
        }

        $baseUrl = config('whatsapp.bridge.base_url');
        $endpoint = config('whatsapp.bridge.endpoints.send_message');
        $url = rtrim($baseUrl, '/').$endpoint;
        $timeout = config('whatsapp.bridge.timeout', 30);

        try {
            $response = Http::timeout($timeout)->post($url, [
                'session_id' => $whatsappData['session_id'],
                'to' => $whatsappData['to'],
                'message' => $whatsappData['message'],
            ]);

            if ($response->successful()) {
                Log::info('[WHATSAPP_CHANNEL] Notification sent successfully', [
                    'to' => $whatsappData['to'],
                    'session_id' => $whatsappData['session_id'],
                ]);
            } else {
                Log::error('[WHATSAPP_CHANNEL] Failed to send notification', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'to' => $whatsappData['to'],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('[WHATSAPP_CHANNEL] Exception sending notification', [
                'error' => $e->getMessage(),
                'to' => $whatsappData['to'] ?? 'unknown',
            ]);
        }
    }

    private function hasRequiredFields(array $data): bool
    {
        return isset($data['session_id'], $data['to'], $data['message']);
    }
}
