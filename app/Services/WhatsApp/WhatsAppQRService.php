<?php

namespace App\Services\WhatsApp;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class WhatsAppQRService
{
    // Configuration optimisée
    private const MAX_RETRIES = 8;
    private const RETRY_DELAY = 3;
    private const QR_SIZE = 300;
    private const HTTP_TIMEOUT = 12;
    private const CONNECT_TIMEOUT = 6;
    private const SESSION_CACHE_TTL = 300; // 5 minutes

    private string $bridgeUrl;
    private ?string $apiToken;

    public function __construct()
    {
        $this->bridgeUrl = config('whatsapp.node_js.base_url');
        $this->apiToken = config('whatsapp.node_js.api_token');
    }

    /**
     * Générer un QR code avec session name personnalisé
     */
    public function generateQRCode(string $sessionName, int $userId): array
    {
        Log::info('📱 QRService: Starting QR generation with custom session name', [
            'user_id' => $userId,
            'session_name' => $sessionName,
        ]);

        try {
            $sessionId = $this->createUniqueSessionId($userId);

            Log::info('🆔 QRService: Generated unique session ID', [
                'session_id' => $sessionId,
                'user_session_name' => $sessionName,
            ]);

            Log::info('📡 QRService: Creating session...');
            $sessionData = $this->createSession($sessionId, $userId);

            Log::info('🎯 QRService: Requesting QR code...');
            $qrCode = $this->fetchQRCode($sessionId);

            Log::info('💾 QRService: Generating QR file...');
            $fileInfo = $this->generateQRFile($qrCode, $userId);

            Log::info('🎉 QRService: QR generation completed successfully', [
                'session_id' => $sessionId,
                'filename' => $fileInfo['filename'],
            ]);

            return [
                'success' => true,
                'qr_code' => $qrCode,
                'session_id' => $sessionId,
                'session_name' => $sessionName,
                'filename' => $fileInfo['filename'],
                'url' => $fileInfo['url'],
            ];

        } catch (Exception $e) {
            Log::error('❌ QRService: QR generation failed', [
                'user_id' => $userId,
                'session_name' => $sessionName,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    // =====================================================
    // MÉTHODES PRIVÉES
    // =====================================================

    /**
     * Créer un ID de session unique pour SaaS (évite les conflits Puppeteer)
     */
    private function createUniqueSessionId(int $userId): string
    {
        $timestamp = (int) (microtime(true) * 10000); // Microsecondes pour plus d'unicité
        $random = substr(md5(uniqid().microtime()), 0, 8);

        return "session_{$userId}_{$timestamp}_{$random}";
    }

    /**
     * Créer une session WhatsApp
     */
    private function createSession(string $sessionId, int $userId): array
    {
        $url = "{$this->bridgeUrl}/api/sessions/create";
        $payload = [
            'sessionId' => $sessionId,
            'userId' => $userId,
        ];

        Log::info('📡 QRService: Creating session via HTTP', [
            'url' => $url,
            'payload' => $payload,
            'generated_session_id' => $sessionId,
            'user_id' => $userId,
        ]);

        $response = $this->makeHttpRequest('POST', $url, $payload);

        Log::info('📨 QRService: HTTP Response received', [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'headers' => $response->headers(),
            'body_raw' => $response->body(),
            'sent_session_id' => $sessionId,
        ]);

        if (! $response->successful()) {
            throw new Exception("Failed to create WhatsApp session: HTTP {$response->status()} - {$response->body()}");
        }

        $data = $response->json();

        Log::info('📄 QRService: Response data parsed', [
            'data' => $data,
            'data_type' => gettype($data),
            'has_success_key' => isset($data['success']),
            'success_value' => $data['success'] ?? 'NOT_SET',
        ]);

        if (empty($data) || ! isset($data['success'])) {
            throw new Exception('Invalid response from WhatsApp bridge: '.$response->body());
        }

        if ($data['success'] === false) {
            Log::error('🚫 QRService: Bridge returned success=false', [
                'full_response' => $data,
                'message' => $data['message'] ?? 'No message provided',
                'error' => $data['error'] ?? 'No error provided',
            ]);
            throw new Exception($data['message'] ?? 'Unknown error creating session');
        }

        Log::info('✅ QRService: Session created successfully', [
            'session_id' => $sessionId,
            'response_data' => $data,
        ]);

        return $data;
    }

    /**
     * Récupérer le QR Code avec polling intelligent et timeout adaptatif
     */
    private function fetchQRCode(string $sessionId): string
    {
        $url = "{$this->bridgeUrl}/api/sessions/{$sessionId}/qr";
        $maxWaitTime = 120; // 2 minutes max
        $startTime = time();

        Log::info('🔍 QRService: Starting intelligent QR polling', [
            'url' => $url,
            'session_id' => $sessionId,
            'max_wait_time' => $maxWaitTime,
        ]);

        // Phase 1: Attente rapide (5 tentatives avec 2s d'intervalle)
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            if ($attempt > 1) {
                sleep(2);
            }

            $qrCode = $this->tryFetchQRCode($url, $sessionId, $attempt, 'rapid');
            if ($qrCode) {
                return $qrCode;
            }

            if ((time() - $startTime) >= $maxWaitTime) {
                throw new Exception("QR code generation timeout after {$maxWaitTime} seconds");
            }
        }

        // Phase 2: Attente progressive (délais croissants)
        $delays = [3, 5, 7, 10, 15, 20];
        for ($i = 0; $i < count($delays) && (time() - $startTime) < $maxWaitTime; $i++) {
            sleep($delays[$i]);

            $qrCode = $this->tryFetchQRCode($url, $sessionId, $i + 6, 'progressive');
            if ($qrCode) {
                return $qrCode;
            }
        }

        throw new Exception("QR code not available after {$maxWaitTime} seconds. WhatsApp-web.js initialization may have failed.");
    }

    /**
     * Tentative unique de récupération QR avec logging détaillé
     */
    private function tryFetchQRCode(string $url, string $sessionId, int $attempt, string $phase): ?string
    {
        try {
            Log::info("🔄 QRService: QR fetch attempt {$attempt} ({$phase} phase)");

            $response = $this->makeHttpRequest('GET', $url);

            if ($response->successful()) {
                $data = $response->json();

                if (! empty($data['qrCode'])) {
                    Log::info('✅ QRService: QR code retrieved successfully', [
                        'attempt' => $attempt,
                        'phase' => $phase,
                        'qr_length' => strlen($data['qrCode']),
                    ]);

                    return $data['qrCode'];
                } else {
                    Log::info('⏳ QRService: QR not ready yet', [
                        'attempt' => $attempt,
                        'phase' => $phase,
                        'response' => $data,
                    ]);
                }
            } elseif ($response->status() === 404) {
                Log::info('⏳ QRService: Session initializing', [
                    'attempt' => $attempt,
                    'phase' => $phase,
                ]);
            } else {
                Log::warning('⚠️ QRService: Unexpected response', [
                    'attempt' => $attempt,
                    'phase' => $phase,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (Exception $e) {
            Log::warning("⚠️ QRService: HTTP error on attempt {$attempt}", [
                'phase' => $phase,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Générer le fichier QR Code
     */
    private function generateQRFile(string $qrCode, int $userId): array
    {
        $filename = "qr-{$userId}-".time().'-'.substr(md5(uniqid()), 0, 8).'.svg';
        $directory = public_path('qrcodes');
        $filePath = $directory.'/'.$filename;

        // Directory should exist from Dockerfile, but check anyway
        if (! is_dir($directory)) {
            throw new Exception("QR directory does not exist: {$directory}");
        }

        if (! is_writable($directory)) {
            throw new Exception("QR directory is not writable: {$directory}");
        }

        $qrSvg = QrCode::format('svg')->size(self::QR_SIZE)->generate($qrCode);

        if (file_put_contents($filePath, $qrSvg) === false) {
            throw new Exception("Failed to write QR file: {$filePath}");
        }

        Log::info('QR file generated successfully', [
            'filename' => $filename,
            'size' => filesize($filePath),
        ]);

        return [
            'filename' => $filename,
            'url' => config('app.url')."/qrcodes/{$filename}",
            'path' => $filePath,
            'size' => filesize($filePath),
        ];
    }

    /**
     * Vérifier si une session est connectée
     */
    public function checkSessionConnection(string $sessionId): bool
    {
        try {
            $url = "{$this->bridgeUrl}/api/sessions/{$sessionId}/status";

            Log::debug('🔍 QRService: Checking session connection status', [
                'session_id' => $sessionId,
                'url' => $url,
            ]);

            $response = $this->makeHttpRequest('GET', $url, [], 10); // Timeout court

            if (! $response->successful()) {
                Log::debug('⚠️ QRService: Status check failed', [
                    'session_id' => $sessionId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $data = $response->json();
            $status = $data['status'] ?? 'unknown';
            $isConnected = $status === 'connected';

            Log::debug($isConnected ? '✅ QRService: Session is connected' : '⏳ QRService: Session not connected yet', [
                'session_id' => $sessionId,
                'status' => $status,
                'full_response' => $data,
            ]);

            return $isConnected;

        } catch (\Exception $e) {
            Log::warning('⚠️ QRService: Connection check error', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Faire une requête HTTP optimisée
     */
    private function makeHttpRequest(string $method, string $url, array $data = [], ?int $customTimeout = null): \Illuminate\Http\Client\Response
    {
        $timeout = $customTimeout ?? self::HTTP_TIMEOUT;

        $http = Http::timeout($timeout)
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->acceptJson()
            ->withHeaders([
                'Connection' => 'close',
                'User-Agent' => 'Laravel-WhatsApp-QR-Service/1.0',
            ]);

        // IMPORTANT: Pas de retry automatique pour POST /create
        // Les retry sont gérés manuellement dans fetchQRCode()
        if (str_contains($url, '/api/sessions/create')) {
            Log::info('🚫 QRService: Disabling auto-retry for session creation', [
                'url' => $url,
            ]);
            // Pas de ->retry() pour éviter les sessions multiples
        } else {
            $http = $http->retry(2, 500); // 2 essais avec 500ms d'attente
        }

        // Ajouter le token si disponible
        if ($this->apiToken) {
            $http = $http->withToken($this->apiToken);
        }

        // Faire la requête selon la méthode
        return match (strtoupper($method)) {
            'GET' => $http->get($url),
            'POST' => $http->post($url, $data),
            'PUT' => $http->put($url, $data),
            'DELETE' => $http->delete($url),
            default => throw new Exception("Unsupported HTTP method: {$method}")
        };
    }
}
