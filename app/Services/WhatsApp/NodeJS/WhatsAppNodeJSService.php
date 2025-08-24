<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\NodeJS;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class WhatsAppNodeJSService
{
    private string $nodeJSBaseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->nodeJSBaseUrl = config('whatsapp.node_js.base_url', 'http://localhost:3000');
        $this->timeout = config('whatsapp.node_js.timeout', 30);
    }

    public function sendTextMessage(string $sessionId, string $phoneNumber, string $message): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->nodeJSBaseUrl}/api/send-message", [
                    'session_id' => $sessionId,
                    'phone' => $phoneNumber,
                    'message' => $message,
                    'type' => 'text',
                ]);

            return $this->handleMessageResponse($response, $sessionId, $phoneNumber, 'text');

        } catch (Exception $e) {
            $this->logException('text', $sessionId, $phoneNumber, $e);

            return false;
        }
    }

    public function sendMediaMessage(
        string $sessionId,
        string $phoneNumber,
        string $mediaUrl,
        string $mediaType = 'image',
        string $caption = ''
    ): bool {
        try {
            $response = Http::timeout(60)
                ->post("{$this->nodeJSBaseUrl}/api/send-media", [
                    'session_id' => $sessionId,
                    'phone' => $phoneNumber,
                    'media_url' => $mediaUrl,
                    'media_type' => $mediaType,
                    'caption' => $caption,
                ]);

            return $this->handleMediaResponse($response, $sessionId, $phoneNumber, $mediaType, $mediaUrl);

        } catch (Exception $e) {
            $this->logMediaException($sessionId, $phoneNumber, $mediaType, $e);

            return false;
        }
    }

    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->nodeJSBaseUrl}/health");

            return $response->successful();

        } catch (Exception $e) {
            Log::warning('[NODE_JS] Service inaccessible', [
                'url' => $this->nodeJSBaseUrl,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function handleMessageResponse(Response $response, string $sessionId, string $phoneNumber, string $type): bool
    {
        if ($response->successful()) {
            Log::info('[NODE_JS] Message texte envoyé avec succès', [
                'session_id' => $sessionId,
                'phone' => $phoneNumber,
                'type' => $type,
            ]);

            return true;
        }

        Log::error('[NODE_JS] Erreur envoi message texte', [
            'session_id' => $sessionId,
            'phone' => $phoneNumber,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }

    private function handleMediaResponse(Response $response, string $sessionId, string $phoneNumber, string $mediaType, string $mediaUrl): bool
    {
        if ($response->successful()) {
            Log::info('[NODE_JS] Média envoyé avec succès', [
                'session_id' => $sessionId,
                'phone' => $phoneNumber,
                'media_type' => $mediaType,
                'media_url' => $mediaUrl,
            ]);

            return true;
        }

        Log::error('[NODE_JS] Erreur envoi média', [
            'session_id' => $sessionId,
            'phone' => $phoneNumber,
            'media_type' => $mediaType,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }

    private function logException(string $type, string $sessionId, string $phoneNumber, Exception $e): void
    {
        Log::error('[NODE_JS] Exception envoi message texte', [
            'session_id' => $sessionId,
            'phone' => $phoneNumber,
            'type' => $type,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    private function logMediaException(string $sessionId, string $phoneNumber, string $mediaType, Exception $e): void
    {
        Log::error('[NODE_JS] Exception envoi média', [
            'session_id' => $sessionId,
            'phone' => $phoneNumber,
            'media_type' => $mediaType,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
