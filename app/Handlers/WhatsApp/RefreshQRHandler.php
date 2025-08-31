<?php

declare(strict_types=1);

namespace App\Handlers\WhatsApp;

use App\DTOs\WhatsApp\QRRefreshResultDTO;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\WhatsAppQRService;
use Illuminate\Support\Facades\Cache;

final readonly class RefreshQRHandler
{
    public function __construct(
        private WhatsAppQRService $qrService
    ) {}

    public function handle(WhatsAppAccount $account): QRRefreshResultDTO
    {
        $result = $this->qrService->generateQRCode($account->session_name, $account->user_id);

        if (! $result['success']) {
            throw new \Exception('Failed to generate QR code');
        }

        $dto = new QRRefreshResultDTO(
            qrCode: $result['qr_code'],
            sessionId: $result['session_id'],
            expiresAt: now()->addMinutes(5),
        );

        Cache::put("refresh_session:{$account->id}", $dto->sessionId, $dto->expiresAt);

        return $dto;
    }

    public function getCachedSessionId(int $accountId): ?string
    {
        return Cache::get("refresh_session:{$accountId}");
    }

    public function clearCache(int $accountId): void
    {
        Cache::forget("refresh_session:{$accountId}");
    }
}
