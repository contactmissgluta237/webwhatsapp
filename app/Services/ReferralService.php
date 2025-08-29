<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\InternalTransaction;
use App\Models\ReferralEarning;
use App\Models\SystemRevenue;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\DB;

final class ReferralService
{
    /**
     * Distribute referral earnings when a subscription is created
     */
    public function distributeReferralEarnings(UserSubscription $subscription, float $amount): void
    {
        $user = $subscription->user;

        // Check if user has a referrer
        if (! $user->referrer_id) {
            // No referrer, everything goes to system
            $this->recordSystemRevenue($subscription, $amount);

            return;
        }

        $referrer = User::find($user->referrer_id);
        if (! $referrer || ! $referrer->wallet) {
            // Referrer not found or no wallet, everything goes to system
            $this->recordSystemRevenue($subscription, $amount);

            return;
        }

        DB::transaction(function () use ($subscription, $user, $referrer, $amount) {
            // Calculate referrer commission
            $commissionPercentage = floatval($referrer->referral_commission_percentage ?? 10.00);
            $commissionAmount = ($amount * $commissionPercentage) / 100;
            $systemAmount = $amount - $commissionAmount;

            // 1. Credit referrer wallet
            $transaction = $this->creditReferrerWallet($referrer, $commissionAmount, $subscription);

            // 2. Record referral earning
            ReferralEarning::recordEarning(
                $referrer,
                $user,
                $subscription,
                $amount,
                $commissionPercentage,
                $commissionAmount,
                $transaction
            );

            // 3. Record system revenue
            $this->recordSystemRevenue($subscription, $systemAmount);
        });
    }

    /**
     * Credit the referrer's wallet with commission amount
     */
    private function creditReferrerWallet(
        User $referrer,
        float $commissionAmount,
        UserSubscription $subscription
    ): InternalTransaction {
        if (! $referrer->wallet) {
            throw new \Exception('Referrer wallet not found');
        }

        // Credit the wallet
        $referrer->wallet->increment('balance', $commissionAmount);

        // Create internal transaction
        return InternalTransaction::create([
            'wallet_id' => $referrer->wallet->id,
            'amount' => $commissionAmount,
            'transaction_type' => TransactionType::CREDIT(),
            'status' => TransactionStatus::COMPLETED(),
            'description' => "Referral commission - {$subscription->user->full_name} subscription to {$subscription->package->display_name}",
            'related_type' => UserSubscription::class,
            'related_id' => $subscription->id,
            'recipient_user_id' => $referrer->id,
            'created_by' => $subscription->user_id,
            'completed_at' => now(),
        ]);
    }

    /**
     * Record system revenue from subscription
     */
    private function recordSystemRevenue(UserSubscription $subscription, float $amount): void
    {
        SystemRevenue::recordSubscriptionRevenue($subscription, $amount);
    }

    /**
     * Calculate total earnings for a referrer
     */
    public function calculateTotalEarnings(User $referrer): float
    {
        return (float) ReferralEarning::where('referrer_id', $referrer->id)
            ->sum('commission_amount') ?: 0.0;
    }

    /**
     * Get referral statistics for a user
     *
     * @return array{total_referrals: int, active_referrals: int, total_earnings: float, total_transactions: int, average_commission: float, current_commission_rate: float}
     */
    public function getReferralStats(User $referrer): array
    {
        $earnings = ReferralEarning::where('referrer_id', $referrer->id)->get();
        $totalReferrals = $referrer->referrals()->count();
        $activeReferrals = $referrer->referrals()
            ->whereHas('subscriptions', function ($q) {
                $q->where('status', 'active')
                    ->where('ends_at', '>', now());
            })
            ->count();

        return [
            'total_referrals' => $totalReferrals,
            'active_referrals' => $activeReferrals,
            'total_earnings' => $earnings->sum('commission_amount'),
            'total_transactions' => $earnings->count(),
            'average_commission' => $earnings->avg('commission_amount') ?: 0,
            'current_commission_rate' => $referrer->referral_commission_percentage,
        ];
    }

    /**
     * Update commission rate for a user
     */
    public function updateCommissionRate(User $user, float $percentage): bool
    {
        if ($percentage < 0 || $percentage > 50) {
            return false; // Invalid rate
        }

        $user->update(['referral_commission_percentage' => $percentage]);

        return true;
    }

    /**
     * Get top referrers by earnings
     *
     * @return array<int, array{user: User, total_earnings: float, total_referrals: int, commission_rate: float}>
     */
    public function getTopReferrers(int $limit = 10): array
    {
        return ReferralEarning::select('referrer_id')
            ->selectRaw('SUM(commission_amount) as total_earnings')
            ->selectRaw('COUNT(*) as total_referrals')
            ->with('referrer:id,first_name,last_name,referral_commission_percentage')
            ->groupBy('referrer_id')
            ->orderByDesc('total_earnings')
            ->limit($limit)
            ->get()
            ->map(function ($earning) {
                return [
                    'user' => $earning->referrer,
                    'total_earnings' => (float) $earning->getAttributeValue('total_earnings'),
                    'total_referrals' => (int) $earning->getAttributeValue('total_referrals'),
                    'commission_rate' => $earning->referrer->referral_commission_percentage,
                ];
            })
            ->toArray();
    }

    /**
     * Calculate potential earning for a given amount
     *
     * @return array{amount: float, commission_rate: float, commission_amount: float, system_amount: float}
     */
    public function calculatePotentialEarning(User $referrer, float $amount): array
    {
        $commissionRate = $referrer->referral_commission_percentage ?? 10.00;
        $commission = ($amount * $commissionRate) / 100;

        return [
            'amount' => $amount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commission,
            'system_amount' => $amount - $commission,
        ];
    }
}
