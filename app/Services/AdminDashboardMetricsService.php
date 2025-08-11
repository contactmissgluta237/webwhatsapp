<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Dashboard\AdminDashboardMetricsDTO;
use App\DTOs\Dashboard\PeriodDTO;
use App\DTOs\Dashboard\SystemAccountBalanceDTO;
use App\Enums\ExternalTransactionType;
use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Models\ExternalTransaction;
use App\Models\SystemAccount;
use App\Models\SystemAccountTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class AdminDashboardMetricsService
{
    public function getMetrics(?Carbon $startDate = null, ?Carbon $endDate = null): AdminDashboardMetricsDTO
    {
        $startDate = $startDate ?: Carbon::now()->startOfMonth();
        $endDate = $endDate ?: Carbon::now()->endOfMonth();

        return new AdminDashboardMetricsDTO(
            registeredUsers: $this->getRegisteredUsersCount($startDate, $endDate),
            totalWithdrawals: $this->getTotalWithdrawals($startDate, $endDate),
            totalRecharges: $this->getTotalRecharges($startDate, $endDate),
            companyProfit: $this->getCompanyProfit($startDate, $endDate),
            period: new PeriodDTO(
                start: $startDate->format('Y-m-d'),
                end: $endDate->format('Y-m-d')
            )
        );
    }

    private function getRegisteredUsersCount(Carbon $startDate, Carbon $endDate): int
    {
        return User::role(UserRole::CUSTOMER()->value)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    private function getTotalWithdrawals(Carbon $startDate, Carbon $endDate): float
    {
        return (float) ExternalTransaction::where('transaction_type', ExternalTransactionType::WITHDRAWAL())
            ->where('status', TransactionStatus::COMPLETED())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount') ?: 0.0;
    }

    private function getTotalRecharges(Carbon $startDate, Carbon $endDate): float
    {
        return (float) ExternalTransaction::where('transaction_type', ExternalTransactionType::RECHARGE())
            ->where('status', TransactionStatus::COMPLETED())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount') ?: 0.0;
    }

    private function getCompanyProfit(Carbon $startDate, Carbon $endDate): float
    {
        $systemAccountRecharges = (float) SystemAccountTransaction::where('type', ExternalTransactionType::RECHARGE())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount') ?: 0.0;

        $systemAccountWithdrawals = (float) SystemAccountTransaction::where('type', ExternalTransactionType::WITHDRAWAL())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount') ?: 0.0;

        return $systemAccountRecharges - $systemAccountWithdrawals;
    }

    /**
     * @return Collection<SystemAccountBalanceDTO>
     */
    public function getSystemAccountsBalance(): Collection
    {
        return SystemAccount::where('is_active', true)
            ->get()
            ->map(fn ($account) => SystemAccountBalanceDTO::fromSystemAccount($account));
    }
}
