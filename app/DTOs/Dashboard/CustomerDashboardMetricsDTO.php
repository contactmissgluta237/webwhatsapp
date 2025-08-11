<?php

declare(strict_types=1);

namespace App\DTOs\Dashboard;

use App\DTOs\BaseDTO;

final class CustomerDashboardMetricsDTO extends BaseDTO
{
    public function __construct(
        public readonly float $walletBalance,
        public readonly float $totalRecharges,
        public readonly float $totalWithdrawals,
        public readonly int $pendingTransactions,
        public readonly int $activeReferrals,
        public readonly float $commissionsEarned
    ) {}
}
