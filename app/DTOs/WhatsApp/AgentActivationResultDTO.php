<?php

declare(strict_types=1);

namespace App\DTOs\WhatsApp;

final class AgentActivationResultDTO
{
    public function __construct(
        public readonly bool $canActivate,
        public readonly string $reason,
        public readonly int $currentActiveAgents,
        public readonly int $maxAllowedAgents,
        public readonly bool $hasActiveSubscription,
        public readonly float $walletBalance,
    ) {}

    public static function allow(int $currentActive, int $maxAllowed, bool $hasSubscription, float $balance): self
    {
        return new self(
            canActivate: true,
            reason: 'Activation autorisée',
            currentActiveAgents: $currentActive,
            maxAllowedAgents: $maxAllowed,
            hasActiveSubscription: $hasSubscription,
            walletBalance: $balance,
        );
    }

    public static function deny(string $reason, int $currentActive, int $maxAllowed, bool $hasSubscription, float $balance): self
    {
        return new self(
            canActivate: false,
            reason: $reason,
            currentActiveAgents: $currentActive,
            maxAllowedAgents: $maxAllowed,
            hasActiveSubscription: $hasSubscription,
            walletBalance: $balance,
        );
    }
}
