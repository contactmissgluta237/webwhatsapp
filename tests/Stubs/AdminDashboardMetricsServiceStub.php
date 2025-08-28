<?php

namespace Tests\Stubs;

use App\DTOs\Dashboard\AdminDashboardMetricsDTO;
use App\DTOs\Dashboard\PeriodDTO;
use Illuminate\Support\Collection;

class AdminDashboardMetricsServiceStub
{
    public function getMetrics(PeriodDTO $period): AdminDashboardMetricsDTO
    {
        return new AdminDashboardMetricsDTO(
            registeredUsers: 10,
            totalWithdrawals: 1000.0,
            totalRecharges: 2000.0,
            companyProfit: 500.0,
            period: $period
        );
    }

    public function getSystemAccountsBalance(): Collection
    {
        return collect([
            (object) ['type' => 'Orange Money', 'balance' => 1000.0, 'icon' => 'fa-wallet', 'badge' => 'success'],
            (object) ['type' => 'MTN Mobile Money', 'balance' => 2000.0, 'icon' => 'fa-money', 'badge' => 'info'],
        ]);
    }
}
