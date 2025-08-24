<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Dashboard\CustomerDashboardMetricsDTO;
use App\Models\User;

final class CustomerDashboardMetricsService
{
    public function getMetrics(User $customer): CustomerDashboardMetricsDTO
    {
        $wallet = $customer->wallet;
        $activeSubscription = $customer->activeSubscription;

        if (! $wallet) {
            return new CustomerDashboardMetricsDTO(
                walletBalance: 0.0,
                activePackageName: $activeSubscription?->package?->display_name,
                packageExpirationDate: $activeSubscription?->ends_at?->format('d/m/Y'),
                messagesUsed: $activeSubscription?->getTotalMessagesUsed() ?? 0,
                messagesLimit: $activeSubscription?->messages_limit ?? 0,
                remainingMessages: $activeSubscription?->getRemainingMessages() ?? 0,
                activeReferrals: 0,
                commissionsEarned: 0.0
            );
        }

        return new CustomerDashboardMetricsDTO(
            walletBalance: (float) $wallet->balance,
            activePackageName: $activeSubscription?->package?->display_name,
            packageExpirationDate: $activeSubscription?->ends_at?->format('d/m/Y'),
            messagesUsed: $activeSubscription?->getTotalMessagesUsed() ?? 0,
            messagesLimit: $activeSubscription?->messages_limit ?? 0,
            remainingMessages: $activeSubscription?->getRemainingMessages() ?? 0,
            activeReferrals: $this->getActiveReferralsCount($customer),
            commissionsEarned: $this->getCommissionsEarned($customer)
        );
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
