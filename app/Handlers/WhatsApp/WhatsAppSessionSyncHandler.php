<?php

declare(strict_types=1);

namespace App\Handlers\WhatsApp;

use App\Models\WhatsAppAccount;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class WhatsAppSessionSyncHandler
{
    public function __construct(
        private string $bridgeBaseUrl,
        private int $timeout = 30
    ) {}

    public function deleteSessionSafely(WhatsAppAccount $account): void
    {
        $sessionId = $account->session_id;

        Log::info('[SESSION_SYNC] Starting safe session deletion', [
            'session_id' => $sessionId,
            'account_id' => $account->id,
            'user_id' => $account->user_id,
        ]);

        try {
            $this->deleteSessionFromNodejs($sessionId);
            $this->deleteSessionFromLaravel($account);

            Log::info('[SESSION_SYNC] ✅ Session deletion completed successfully', [
                'session_id' => $sessionId,
                'account_id' => $account->id,
            ]);

        } catch (Exception $e) {
            Log::error('[SESSION_SYNC] ❌ Session deletion failed', [
                'session_id' => $sessionId,
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function deleteSessionFromNodejs(string $sessionId): void
    {
        Log::info('[SESSION_SYNC] Deleting session from Node.js first', [
            'session_id' => $sessionId,
            'url' => $this->getDeleteEndpoint($sessionId),
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->delete($this->getDeleteEndpoint($sessionId));

            if (! $response->successful()) {
                throw new RequestException($response);
            }

            $responseData = $response->json();

            Log::info('[SESSION_SYNC] ✅ Node.js session deletion successful', [
                'session_id' => $sessionId,
                'response_data' => $responseData,
            ]);

        } catch (ConnectionException $e) {
            Log::error('[SESSION_SYNC] ❌ Node.js connection failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Impossible de contacter le service WhatsApp. Veuillez réessayer plus tard.');
        } catch (RequestException $e) {
            $statusCode = $e->response?->status();
            $errorMessage = $e->response?->json()['message'] ?? $e->getMessage();

            Log::error('[SESSION_SYNC] ❌ Node.js request failed', [
                'session_id' => $sessionId,
                'status_code' => $statusCode,
                'error' => $errorMessage,
            ]);

            if ($statusCode === 404) {
                Log::warning('[SESSION_SYNC] ⚠️ Session not found in Node.js, proceeding with Laravel deletion', [
                    'session_id' => $sessionId,
                ]);

                return;
            }

            throw new Exception("Erreur lors de la suppression de la session WhatsApp: {$errorMessage}");
        }
    }

    private function deleteSessionFromLaravel(WhatsAppAccount $account): void
    {
        Log::info('[SESSION_SYNC] Deleting session from Laravel database', [
            'session_id' => $account->session_id,
            'account_id' => $account->id,
        ]);

        $account->delete();

        Log::info('[SESSION_SYNC] ✅ Laravel session deletion successful', [
            'session_id' => $account->session_id,
            'account_id' => $account->id,
        ]);
    }

    private function getDeleteEndpoint(string $sessionId): string
    {
        return "{$this->bridgeBaseUrl}/api/sessions/{$sessionId}";
    }
}
