<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Helpers\WhatsAppPhoneHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class WhatsAppNotificationHandler
{
    /**
     * Send WhatsApp notification message
     */
    public function sendNotification(
        string $sessionId,
        string $toPhoneNumber,
        string $message
    ): bool {
        $whatsappTo = WhatsAppPhoneHelper::transformUsablePhoneToWhatsappIdentity($toPhoneNumber);

        $baseUrl = config('whatsapp.bridge.base_url');
        $endpoint = config('whatsapp.bridge.endpoints.send_message');
        $timeout = config('whatsapp.bridge.timeout', 30);

        $url = rtrim($baseUrl, '/').$endpoint;

        $payload = [
            'session_id' => $sessionId,
            'to' => $whatsappTo,
            'message' => $message,
        ];

        Log::info('[WHATSAPP_HANDLER] Attempting to send message', [
            'url' => $url,
            'payload' => $payload,
        ]);

        try {
            $response = Http::timeout($timeout)->post($url, $payload);

            if ($response->successful()) {
                Log::info('[WHATSAPP_HANDLER] Message sent successfully', [
                    'to' => $whatsappTo,
                    'session_id' => $sessionId,
                ]);

                return true;
            }

            Log::error('[WHATSAPP_HANDLER] Failed to send message', [
                'status' => $response->status(),
                'response_body' => $response->body(),
                'to' => $whatsappTo,
                'session_id' => $sessionId,
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('[WHATSAPP_HANDLER] Exception sending message', [
                'error' => $e->getMessage(),
                'to' => $whatsappTo,
                'session_id' => $sessionId,
            ]);

            return false;
        }
    }
}
