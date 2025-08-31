<?php

declare(strict_types=1);

namespace App\DTOs\WhatsApp;

use Carbon\Carbon;
use Livewire\Wireable;

final class QRRefreshResultDTO implements Wireable
{
    public function __construct(
        public string $qrCode,
        public string $sessionId,
        public Carbon $expiresAt,
    ) {}

    public function isExpired(): bool
    {
        return $this->expiresAt->isPast();
    }

    public function toLivewire(): array
    {
        return [
            'qrCode' => $this->qrCode,
            'sessionId' => $this->sessionId,
            'expiresAt' => $this->expiresAt->toISOString(),
        ];
    }

    public static function fromLivewire($value): static
    {
        return new self(
            qrCode: $value['qrCode'],
            sessionId: $value['sessionId'],
            expiresAt: Carbon::parse($value['expiresAt']),
        );
    }
}
