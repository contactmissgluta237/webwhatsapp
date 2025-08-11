<?php

namespace App\Services\WhatsApp;

use Exception;
use Illuminate\Support\Facades\Http;

class WhatsAppMessageService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.whatsapp_bridge.url');
    }

    public function sendMessage(string $to, string $message): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/send-message", [
                'to' => $this->formatPhoneNumber($to),
                'message' => $message,
            ]);

            return $response->successful();
        } catch (Exception $e) {
            throw new Exception("Erreur envoi message: {$e->getMessage()}");
        }
    }

    private function formatPhoneNumber(string $phone): string
    {
        // Nettoie le numéro : +237655332183 devient 237655332183@c.us
        $clean = preg_replace('/[^0-9]/', '', $phone);

        return $clean.'@c.us';
    }
}
