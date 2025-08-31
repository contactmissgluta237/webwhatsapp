<?php

declare(strict_types=1);

namespace App\DTOs\WhatsApp;

use App\Models\WhatsAppAccount;
use Livewire\Wireable;

final class ConnectionVerificationDTO implements Wireable
{
    public function __construct(
        public bool $isConnected,
        public int $attempts,
        public bool $shouldContinue,
        public int $nextIntervalSeconds,
        public ?WhatsAppAccount $refreshedAccount = null,
    ) {}

    public function getProgressPercentage(): int
    {
        return (int) (($this->attempts / 60) * 100);
    }

    public function getEstimatedRemainingMinutes(): int
    {
        $remaining = max(0, 60 - $this->attempts);

        return (int) (($remaining * 3) / 60);
    }

    public function toLivewire(): array
    {
        return [
            'isConnected' => $this->isConnected,
            'attempts' => $this->attempts,
            'shouldContinue' => $this->shouldContinue,
            'nextIntervalSeconds' => $this->nextIntervalSeconds,
            'refreshedAccount' => $this->refreshedAccount?->id,
        ];
    }

    public static function fromLivewire($value): static
    {
        return new self(
            isConnected: $value['isConnected'],
            attempts: $value['attempts'],
            shouldContinue: $value['shouldContinue'],
            nextIntervalSeconds: $value['nextIntervalSeconds'],
            refreshedAccount: $value['refreshedAccount'] ? WhatsAppAccount::find($value['refreshedAccount']) : null,
        );
    }
}
