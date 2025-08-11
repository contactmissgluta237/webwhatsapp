<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Dashboard\CustomerDashboardMetricsDTO;
use App\Enums\ExternalTransactionType;
use App\Enums\TransactionStatus;
use App\Models\ExternalTransaction;
use App\Models\User;

final class CustomerDashboardMetricsService
{
    public function getMetrics(User $customer): CustomerDashboardMetricsDTO
    {
        $wallet = $customer->wallet;

        if (! $wallet) {
            return new CustomerDashboardMetricsDTO(
                walletBalance: 0.0,
                totalRecharges: 0.0,
                totalWithdrawals: 0.0,
                pendingTransactions: 0,
                activeReferrals: 0,
                commissionsEarned: 0.0
            );
        }

        return new CustomerDashboardMetricsDTO(
            walletBalance: (float) $wallet->balance,
            totalRecharges: $this->getTotalRecharges($wallet->id),
            totalWithdrawals: $this->getTotalWithdrawals($wallet->id),
            pendingTransactions: $this->getPendingTransactionsCount($wallet->id),
            activeReferrals: $this->getActiveReferralsCount($customer),
            commissionsEarned: $this->getCommissionsEarned($customer)
        );
    }

    private function getTotalRecharges(int $walletId): float
    {
        return (float) ExternalTransaction::where('wallet_id', $walletId)
            ->where('transaction_type', ExternalTransactionType::RECHARGE())
            ->where('status', TransactionStatus::COMPLETED())
            ->sum('amount') ?: 0.0;
    }

    private function getTotalWithdrawals(int $walletId): float
    {
        return (float) ExternalTransaction::where('wallet_id', $walletId)
            ->where('transaction_type', ExternalTransactionType::WITHDRAWAL())
            ->where('status', TransactionStatus::COMPLETED())
            ->sum('amount') ?: 0.0;
    }

    private function getPendingTransactionsCount(int $walletId): int
    {
        return ExternalTransaction::where('wallet_id', $walletId)
            ->where('status', TransactionStatus::PENDING())
            ->count();
    }

    private function getActiveReferralsCount(User $customer): int
    {
        return $customer->referrals()->count() ?? 0;
    }

    private function getCommissionsEarned(User $customer): float
    {
        return 0.0;
    }
}
