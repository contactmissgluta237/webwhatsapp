<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class NodeJsBackupService
{
    private readonly string $nodeJsBaseUrl;

    public function __construct()
    {
        $this->nodeJsBaseUrl = config('whatsapp.node_js.base_url');
    }

    /**
     * Force la sauvegarde des sessions actives sur le service Node.js
     */
    public function forceSaveActiveSessions(): bool
    {
        try {
            $response = Http::timeout(10)->post($this->nodeJsBaseUrl.'/api/sessions/save');

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Node.js session backup triggered successfully', [
                    'response' => $data,
                    'session_count' => $data['sessionCount'] ?? 'unknown',
                ]);

                return true;
            }

            Log::warning('Node.js session backup failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Error triggering Node.js session backup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Sauvegarde une session spécifique (OPTIMISÉ)
     */
    public function saveSpecificSession(string $sessionId): bool
    {
        try {
            $url = $this->nodeJsBaseUrl."/api/sessions/{$sessionId}/save";

            Log::info('Triggering specific session backup', [
                'session_id' => $sessionId,
                'url' => $url,
            ]);

            $response = Http::timeout(10)->post($url);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Specific session backup completed successfully', [
                    'session_id' => $sessionId,
                    'response' => $data,
                ]);

                return true;
            }

            Log::warning('Specific session backup failed', [
                'session_id' => $sessionId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Error triggering specific session backup', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Vérifie si le service Node.js est accessible
     */
    public function checkHealth(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->nodeJsBaseUrl.'/health');

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Node.js service health check failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
