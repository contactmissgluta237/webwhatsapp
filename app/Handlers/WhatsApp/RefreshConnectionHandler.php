<?php

declare(strict_types=1);

namespace App\Handlers\WhatsApp;

use App\DTOs\WhatsApp\ConnectionVerificationDTO;
use App\DTOs\WhatsApp\WhatsAppSessionStatusDTO;
use App\Enums\WhatsAppStatus;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\WhatsAppQRService;
use App\Traits\WhatsApp\CalculatesConnectionIntervals;
use Illuminate\Support\Facades\DB;

final readonly class RefreshConnectionHandler
{
    use CalculatesConnectionIntervals;

    public function __construct(
        private WhatsAppQRService $qrService,
        private RefreshQRHandler $qrHandler
    ) {}

    public function handle(WhatsAppAccount $account, string $sessionId, int $attempt): ConnectionVerificationDTO
    {
        $sessionStatus = $this->qrService->getSessionStatus($sessionId);
        $isConnected = $sessionStatus?->isConnected() ?? false;

        $refreshedAccount = null;
        if ($isConnected) {
            $refreshedAccount = $this->updateAccount($account, $sessionId, $sessionStatus);
            $this->qrHandler->clearCache($account->id);
        }

        return new ConnectionVerificationDTO(
            isConnected: $isConnected,
            attempts: $attempt,
            shouldContinue: $attempt < 60 && ! $isConnected,
            nextIntervalSeconds: $this->calculateNextInterval($attempt),
            refreshedAccount: $refreshedAccount,
        );
    }

    private function updateAccount(WhatsAppAccount $account, string $sessionId, WhatsAppSessionStatusDTO $sessionStatus): WhatsAppAccount
    {
        return DB::transaction(function () use ($account, $sessionId, $sessionStatus) {
            $account->update([
                'session_id' => $sessionId,
                'status' => WhatsAppStatus::CONNECTED(),
                'phone_number' => $sessionStatus->phoneNumber,
                'last_seen_at' => $sessionStatus->lastActivity ?? now(),
            ]);

            return $account->fresh();
        });
    }
}
