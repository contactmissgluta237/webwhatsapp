<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppSessionStatusDTO;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

final class WhatsAppQRService
{
    private const QR_SIZE = 300;
    private const HTTP_TIMEOUT = 12;
    private const CONNECT_TIMEOUT = 6;

    private string $bridgeUrl;
    private ?string $apiToken;

    public function __construct()
    {
        $this->bridgeUrl = config('whatsapp.node_js.base_url');
        $this->apiToken = config('whatsapp.node_js.api_token');
    }

    public function generateQRCode(string $sessionName, int $userId): array
    {
        Log::info('Starting QR generation', [
            'user_id' => $userId,
            'session_name' => $sessionName,
        ]);

        try {
            $sessionId = $this->createUniqueSessionId($userId);
            $this->createSession($sessionId, $userId);
            $qrCode = $this->fetchQRCode($sessionId);
            $fileInfo = $this->generateQRFile($qrCode, $userId);

            Log::info('QR generation completed successfully', [
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
            Log::error('QR generation failed', [
                'user_id' => $userId,
                'session_name' => $sessionName,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function createUniqueSessionId(int $userId): string
    {
        $timestamp = (int) (microtime(true) * 10000); // Microseconds for more uniqueness
        $random = substr(md5(uniqid().microtime()), 0, 8);

        return "session_{$userId}_{$timestamp}_{$random}";
    }

    private function createSession(string $sessionId, int $userId): array
    {
        $url = "{$this->bridgeUrl}/api/sessions/create";
        $payload = [
            'sessionId' => $sessionId,
            'userId' => $userId,
        ];

        $response = $this->makeHttpRequest('POST', $url, $payload);

        if (! $response->successful()) {
            throw new Exception("Failed to create WhatsApp session: HTTP {$response->status()} - {$response->body()}");
        }

        $data = $response->json();

        if (empty($data) || ! isset($data['success'])) {
            throw new Exception('Invalid response from WhatsApp bridge: '.$response->body());
        }

        if ($data['success'] === false) {
            throw new Exception($data['message'] ?? 'Unknown error creating session');
        }

        return $data;
    }

    private function fetchQRCode(string $sessionId): string
    {
        $url = "{$this->bridgeUrl}/api/sessions/{$sessionId}/qr";
        $maxWaitTime = 120;
        $startTime = time();

        // Fast polling: 5 attempts with 2s intervals
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            if ($attempt > 1) {
                sleep(2);
            }

            $qrCode = $this->tryFetchQRCode($url, $sessionId, $attempt);
            if ($qrCode) {
                return $qrCode;
            }

            if ((time() - $startTime) >= $maxWaitTime) {
                throw new Exception("QR code generation timeout after {$maxWaitTime} seconds");
            }
        }

        // Progressive delays
        $delays = [3, 5, 7, 10, 15, 20];
        for ($i = 0; $i < count($delays) && (time() - $startTime) < $maxWaitTime; $i++) {
            sleep($delays[$i]);

            $qrCode = $this->tryFetchQRCode($url, $sessionId, $i + 6);
            if ($qrCode) {
                return $qrCode;
            }
        }

        throw new Exception("QR code not available after {$maxWaitTime} seconds");
    }

    private function tryFetchQRCode(string $url, string $sessionId, int $attempt): ?string
    {
        try {
            $response = $this->makeHttpRequest('GET', $url);

            if ($response->successful()) {
                $data = $response->json();

                if (! empty($data['qrCode'])) {
                    Log::info('QR code retrieved successfully', [
                        'attempt' => $attempt,
                        'qr_length' => strlen($data['qrCode']),
                    ]);

                    return $data['qrCode'];
                }
            } elseif ($response->status() !== 404) {
                Log::warning('Unexpected QR fetch response', [
                    'attempt' => $attempt,
                    'status' => $response->status(),
                ]);
            }
        } catch (Exception $e) {
            Log::warning("QR fetch error on attempt {$attempt}", [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function generateQRFile(string $qrCode, int $userId): array
    {
        $filename = "qr-{$userId}-".time().'-'.substr(md5(uniqid()), 0, 8).'.svg';
        $directory = public_path('qrcodes');
        $filePath = $directory.'/'.$filename;

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

        return [
            'filename' => $filename,
            'url' => config('app.url')."/qrcodes/{$filename}",
            'path' => $filePath,
            'size' => filesize($filePath),
        ];
    }

    public function getSessionStatus(string $sessionId): ?WhatsAppSessionStatusDTO
    {
        try {
            $url = "{$this->bridgeUrl}/api/sessions/{$sessionId}/status";

            Log::debug('Checking session status', [
                'session_id' => $sessionId,
                'url' => $url,
            ]);

            $response = $this->makeHttpRequest('GET', $url, [], 10);

            if (! $response->successful()) {
                Log::debug('Session status check failed', [
                    'session_id' => $sessionId,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();
            $dto = WhatsAppSessionStatusDTO::fromNodeJsResponse($data);

            Log::debug($dto->isConnected() ? 'Session is connected' : 'Session not connected yet', [
                'session_id' => $sessionId,
                'status' => $dto->status,
                'phone_number' => $dto->phoneNumber,
            ]);

            return $dto;

        } catch (\Exception $e) {
            Log::warning('Session status check error', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function checkSessionConnection(string $sessionId): bool
    {
        $status = $this->getSessionStatus($sessionId);

        return $status?->isConnected() ?? false;
    }

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

        // No auto-retry for session creation to avoid duplicate sessions
        if (! str_contains($url, '/api/sessions/create')) {
            $http = $http->retry(2, 500);
        }

        if ($this->apiToken) {
            $http = $http->withToken($this->apiToken);
        }

        return match (strtoupper($method)) {
            'GET' => $http->get($url),
            'POST' => $http->post($url, $data),
            'PUT' => $http->put($url, $data),
            'DELETE' => $http->delete($url),
            default => throw new Exception("Unsupported HTTP method: {$method}")
        };
    }
}
