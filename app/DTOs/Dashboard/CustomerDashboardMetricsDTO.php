<?php

declare(strict_types=1);

namespace App\DTOs\Dashboard;

use App\DTOs\BaseDTO;

final class CustomerDashboardMetricsDTO extends BaseDTO
{
    public function __construct(
        public readonly float $walletBalance,
        public readonly ?string $activePackageName,
        public readonly ?string $packageExpirationDate,
        public readonly int $messagesUsed,
        public readonly int $messagesLimit,
        public readonly int $remainingMessages,
        public readonly int $activeReferrals,
        public readonly float $commissionsEarned
    ) {}
}
