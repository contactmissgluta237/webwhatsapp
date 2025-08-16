<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class WhatsAppSessionDisconnectionService
{
    private const HTTP_TIMEOUT = 30;
    private const CONNECT_TIMEOUT = 10;

    private string $bridgeUrl;
    private ?string $apiToken;

    public function __construct()
    {
        $this->bridgeUrl = $this->getBridgeUrl();
        $this->apiToken = config('services.whatsapp_bridge.api_token');
    }

    /**
     * Déconnecte une session WhatsApp via le bridge Node.js
     */
    public function disconnectSession(WhatsAppAccount $account): array
    {
        Log::info('🔌 WhatsApp Session Disconnection: Starting disconnection', [
            'account_id' => $account->id,
            'session_name' => $account->session_name,
            'session_id' => $account->session_id,
        ]);

        try {
            if (!$account->session_id) {
                return [
                    'success' => true,
                    'message' => 'Session non connectée, aucune déconnexion nécessaire',
                    'skipped' => true,
                ];
            }

            $disconnectionResult = $this->sendDisconnectionRequest($account->session_id);

            if ($disconnectionResult['success']) {
                Log::info('✅ WhatsApp Session Disconnection: Successfully disconnected', [
                    'account_id' => $account->id,
                    'session_id' => $account->session_id,
                ]);

                return [
                    'success' => true,
                    'message' => 'Session déconnectée avec succès',
                    'node_response' => $disconnectionResult,
                ];
            }

            Log::warning('⚠️ WhatsApp Session Disconnection: Failed but continuing', [
                'account_id' => $account->id,
                'session_name' => $account->session_name,
                'error' => $disconnectionResult['error'] ?? 'Unknown error',
            ]);

            return [
                'success' => false,
                'message' => 'Échec de la déconnexion mais suppression continue',
                'error' => $disconnectionResult['error'] ?? 'Unknown error',
                'continue_deletion' => true,
            ];

        } catch (\Exception $e) {
            Log::error('❌ WhatsApp Session Disconnection: Exception occurred', [
                'account_id' => $account->id,
                'session_name' => $account->session_name,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la déconnexion: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'continue_deletion' => true,
            ];
        }
    }

    /**
     * Déconnecte toutes les sessions d'un utilisateur
     */
    public function disconnectUserSessions(int $userId): array
    {
        Log::info('🔌 WhatsApp Session Disconnection: Starting user sessions disconnection', [
            'user_id' => $userId,
        ]);

        try {
            $disconnectionResult = $this->sendUserDisconnectionRequest($userId);

            if ($disconnectionResult['success']) {
                Log::info('✅ WhatsApp Session Disconnection: User sessions disconnected', [
                    'user_id' => $userId,
                    'destroyed_count' => $disconnectionResult['destroyed'] ?? 0,
                ]);

                return [
                    'success' => true,
                    'message' => 'Sessions utilisateur déconnectées avec succès',
                    'destroyed_count' => $disconnectionResult['destroyed'] ?? 0,
                ];
            }

            Log::warning('⚠️ WhatsApp Session Disconnection: User disconnection failed', [
                'user_id' => $userId,
                'error' => $disconnectionResult['error'] ?? 'Unknown error',
            ]);

            return [
                'success' => false,
                'message' => 'Échec de la déconnexion des sessions utilisateur',
                'error' => $disconnectionResult['error'] ?? 'Unknown error',
                'continue_deletion' => true,
            ];

        } catch (\Exception $e) {
            Log::error('❌ WhatsApp Session Disconnection: User disconnection exception', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la déconnexion: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'continue_deletion' => true,
            ];
        }
    }

    /**
     * Envoie la requête de déconnexion d'une session spécifique
     */
    private function sendDisconnectionRequest(string $sessionId): array
    {
        $url = "{$this->bridgeUrl}/api/sessions/{$sessionId}";

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($this->apiToken) {
            $headers['Authorization'] = "Bearer {$this->apiToken}";
        }

        Log::info('📡 WhatsApp Session Disconnection: Sending DELETE request', [
            'url' => $url,
            'session_id' => $sessionId,
        ]);

        $response = Http::timeout(self::HTTP_TIMEOUT)
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->withHeaders($headers)
            ->delete($url);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'data' => $data,
            ];
        }

        $errorMessage = $response->json('message') ?? 'HTTP Error ' . $response->status();
        
        return [
            'success' => false,
            'error' => $errorMessage,
            'status' => $response->status(),
            'body' => $response->body(),
        ];
    }

    /**
     * Envoie la requête de déconnexion de toutes les sessions d'un utilisateur
     */
    private function sendUserDisconnectionRequest(int $userId): array
    {
        $url = "{$this->bridgeUrl}/api/sessions/reset-user/{$userId}";

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($this->apiToken) {
            $headers['Authorization'] = "Bearer {$this->apiToken}";
        }

        Log::info('📡 WhatsApp Session Disconnection: Sending user reset request', [
            'url' => $url,
            'user_id' => $userId,
        ]);

        $response = Http::timeout(self::HTTP_TIMEOUT)
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->withHeaders($headers)
            ->post($url);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'data' => $data,
                'destroyed' => $data['destroyed'] ?? 0,
            ];
        }

        $errorMessage = $response->json('message') ?? 'HTTP Error ' . $response->status();
        
        return [
            'success' => false,
            'error' => $errorMessage,
            'status' => $response->status(),
            'body' => $response->body(),
        ];
    }

    /**
     * Détermine l'URL du bridge selon l'environnement
     */
    private function getBridgeUrl(): string
    {
        $isDocker = $this->isRunningInDocker();

        return $isDocker
            ? config('services.whatsapp_bridge.docker_url', 'http://whatsapp-bridge:3000')
            : config('services.whatsapp_bridge.url', 'http://localhost:3000');
    }

    /**
     * Détecte si l'application s'exécute dans Docker
     */
    private function isRunningInDocker(): bool
    {
        if (file_exists('/.dockerenv')) {
            return true;
        }

        if (getenv('DOCKER_CONTAINER') || getenv('CONTAINER_NAME')) {
            return true;
        }

        if (file_exists('/proc/1/cgroup')) {
            $cgroup = @file_get_contents('/proc/1/cgroup');
            if ($cgroup && (str_contains($cgroup, 'docker') || str_contains($cgroup, 'containerd'))) {
                return true;
            }
        }

        return false;
    }
}
