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

        if (! $user->referrer_id) {
            $this->recordSystemRevenue($subscription, $amount);

            return;
        }

        $referrer = User::find($user->referrer_id);
        if (! $referrer || ! $referrer->wallet) {
            $this->recordSystemRevenue($subscription, $amount);

            return;
        }

        DB::transaction(function () use ($subscription, $user, $referrer, $amount) {
            // Calculate referrer commission
            $commissionPercentage = floatval($referrer->referral_commission_percentage ?? 10.00);
            $commissionAmount = ($amount * $commissionPercentage) / 100;
            $systemAmount = $amount - $commissionAmount;
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
