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
     * Distribuer les gains de parrainage lors d'une souscription
     */
    public function distributeReferralEarnings(UserSubscription $subscription, float $amount): void
    {
        $user = $subscription->user;

        // Vérifier si l'utilisateur a un parrain
        if (! $user->referrer_id) {
            // Pas de parrain, tout va au système
            $this->recordSystemRevenue($subscription, $amount);

            return;
        }

        $referrer = User::find($user->referrer_id);
        if (! $referrer || ! $referrer->wallet) {
            // Parrain introuvable ou pas de wallet, tout va au système
            $this->recordSystemRevenue($subscription, $amount);

            return;
        }

        DB::transaction(function () use ($subscription, $user, $referrer, $amount) {
            // Calculer la commission du parrain
            $commissionPercentage = $referrer->referral_commission_percentage ?? 10.00;
            $commissionAmount = ($amount * $commissionPercentage) / 100;
            $systemAmount = $amount - $commissionAmount;

            // 1. Créditer le wallet du parrain
            $transaction = $this->creditReferrerWallet($referrer, $commissionAmount, $subscription);

            // 2. Enregistrer le gain de parrainage
            ReferralEarning::recordEarning(
                $referrer,
                $user,
                $subscription,
                $amount,
                $commissionPercentage,
                $commissionAmount,
                $transaction
            );

            // 3. Enregistrer les revenus système
            $this->recordSystemRevenue($subscription, $systemAmount);
        });
    }

    /**
     * Créditer le wallet du parrain
     */
    private function creditReferrerWallet(
        User $referrer,
        float $commissionAmount,
        UserSubscription $subscription
    ): InternalTransaction {
        // Créditer le wallet
        $referrer->wallet->increment('balance', $commissionAmount);

        // Créer la transaction interne
        return InternalTransaction::create([
            'wallet_id' => $referrer->wallet->id,
            'amount' => $commissionAmount,
            'transaction_type' => TransactionType::CREDIT(),
            'status' => TransactionStatus::COMPLETED(),
            'description' => "Commission parrainage - Souscription de {$subscription->user->full_name} au package {$subscription->package->display_name}",
            'related_type' => UserSubscription::class,
            'related_id' => $subscription->id,
            'recipient_user_id' => $referrer->id,
            'created_by' => $subscription->user_id,
            'completed_at' => now(),
        ]);
    }

    /**
     * Enregistrer les revenus système
     */
    private function recordSystemRevenue(UserSubscription $subscription, float $amount): void
    {
        SystemRevenue::recordSubscriptionRevenue($subscription, $amount);
    }

    /**
     * Calculer les gains totaux d'un parrain
     */
    public function calculateTotalEarnings(User $referrer): float
    {
        return (float) ReferralEarning::where('referrer_id', $referrer->id)
            ->sum('commission_amount') ?: 0.0;
    }

    /**
     * Obtenir les statistiques de parrainage d'un utilisateur
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
     * Mettre à jour le taux de commission d'un utilisateur
     */
    public function updateCommissionRate(User $user, float $percentage): bool
    {
        if ($percentage < 0 || $percentage > 50) {
            return false; // Taux invalide
        }

        $user->update(['referral_commission_percentage' => $percentage]);

        return true;
    }

    /**
     * Obtenir le top des parrains par gains
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
                    'total_earnings' => $earning->total_earnings,
                    'total_referrals' => $earning->total_referrals,
                    'commission_rate' => $earning->referrer->referral_commission_percentage,
                ];
            })
            ->toArray();
    }

    /**
     * Calculer le potentiel de gain pour un montant donné
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
