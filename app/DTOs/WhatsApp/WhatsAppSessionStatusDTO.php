<?php

declare(strict_types=1);

namespace App\DTOs\WhatsApp;

use App\DTOs\BaseDTO;
use Carbon\Carbon;

final class WhatsAppSessionStatusDTO extends BaseDTO
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $status,
        public readonly ?string $phoneNumber = null,
        public readonly ?Carbon $lastActivity = null,
        public readonly ?int $userId = null,
        public readonly ?string $qrCode = null,
        public readonly bool $isConnected = false,
    ) {}

    public static function fromNodeJsResponse(array $data): self
    {
        return new self(
            sessionId: $data['sessionId'],
            status: $data['status'],
            phoneNumber: $data['phoneNumber'] ?? null,
            lastActivity: isset($data['lastActivity']) ? Carbon::parse($data['lastActivity']) : null,
            userId: $data['userId'] ?? null,
            qrCode: $data['qrCode'] ?? null,
            isConnected: ($data['status'] ?? '') === 'connected',
        );
    }

    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    public function hasPhoneNumber(): bool
    {
        return ! empty($this->phoneNumber);
    }
}
