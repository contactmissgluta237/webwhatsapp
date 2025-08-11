<?php

declare(strict_types=1);

namespace App\DTOs\Dashboard;

use App\DTOs\BaseDTO;

final class AdminDashboardMetricsDTO extends BaseDTO
{
    public function __construct(
        public readonly int $registeredUsers,
        public readonly float $totalWithdrawals,
        public readonly float $totalRecharges,
        public readonly float $companyProfit,
        public readonly PeriodDTO $period
    ) {}
}
