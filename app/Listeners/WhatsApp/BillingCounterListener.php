<?php

declare(strict_types=1);

namespace App\Listeners\WhatsApp;

use App\Events\WhatsApp\MessageProcessedEvent;
use App\Models\InternalTransaction;
use App\Models\UsageSubscriptionTracker;
use App\Services\WhatsApp\Helpers\MessageCostHelper;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Listener responsible for tracking message usage for billing/package limits
 * Handles quota decrementing and wallet debiting for overages
 */
final class BillingCounterListener
{
    /**
     * Handle the event and update billing counters
     */
    public function handle(MessageProcessedEvent $event): void
    {
        // Seulement traiter si le message AI a réussi
        if (!$event->wasSuccessful()) {
            return;
        }

        $user = $event->account->user;
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            Log::warning('[BILLING_COUNTER] No active subscription found', [
                'user_id' => $user->id,
                'session_id' => $event->getSessionId(),
            ]);
            return;
        }

        $tracker = $subscription->getOrCreateCurrentCycleTracker();
        
        // Calculer le coût du message basé sur les produits dans la réponse
        $products = $event->aiResponse->products ?? collect();
        $messageCost = MessageCostHelper::calculateMessageCost($products);

        DB::transaction(function () use ($tracker, $messageCost, $user, $event, $products) {
            $this->processMessageBilling($tracker, $messageCost, $user, $event, $products);
        });
    }

    private function processMessageBilling(
        UsageSubscriptionTracker $tracker,
        int $messageCost,
        $user,
        MessageProcessedEvent $event,
        $products
    ): void {
        if ($tracker->hasRemainingMessages()) {
            // Cas normal : utiliser le quota
            $this->processNormalQuota($tracker, $messageCost, $products);
            
            Log::debug('[BILLING_COUNTER] Used normal quota', [
                'user_id' => $user->id,
                'message_cost' => $messageCost,
                'remaining_after' => $tracker->messages_remaining,
            ]);
        } else {
            // Cas dépassement : débiter le wallet
            $this->processOverageBilling($tracker, $messageCost, $user, $products);
            
            Log::debug('[BILLING_COUNTER] Processed overage billing', [
                'user_id' => $user->id,
                'message_cost' => $messageCost,
                'overage_total' => $tracker->overage_messages_used,
            ]);
        }
    }

    private function processNormalQuota(
        UsageSubscriptionTracker $tracker,
        int $messageCost,
        $products
    ): void {
        $tracker->increment('messages_used', $messageCost);
        $tracker->decrement('messages_remaining', $messageCost);
        $tracker->increment('base_messages_count');
        
        $mediaCount = MessageCostHelper::getProductsMediaCount($products);
        if ($mediaCount > 0) {
            $tracker->increment('media_messages_count', $mediaCount);
        }
        
        $costInXAF = $messageCost * config('pricing.message_base_cost_xaf', 10);
        $tracker->increment('estimated_cost_xaf', $costInXAF);
        
        $tracker->update(['last_message_at' => now()]);
    }

    private function processOverageBilling(
        UsageSubscriptionTracker $tracker,
        int $messageCost,
        $user,
        $products
    ): void {
        $overageCostXAF = $messageCost * config('pricing.overage.cost_per_message_xaf', 10);
        
        // Débiter le wallet via une transaction interne
        InternalTransaction::create([
            'wallet_id' => $user->wallet->id,
            'amount' => $overageCostXAF,
            'transaction_type' => TransactionType::DEBIT(),
            'status' => TransactionStatus::COMPLETED(),
            'description' => "Dépassement WhatsApp: {$messageCost} message(s)",
            'related_type' => UsageSubscriptionTracker::class,
            'related_id' => $tracker->id,
            'created_by' => $user->id,
            'completed_at' => now(),
        ]);

        // Mettre à jour le solde du wallet
        $user->wallet->decrement('balance', $overageCostXAF);

        // Tracker les statistiques de dépassement
        $tracker->increment('overage_messages_used', $messageCost);
        $tracker->increment('overage_cost_paid_xaf', $overageCostXAF);
        
        $mediaCount = MessageCostHelper::getProductsMediaCount($products);
        if ($mediaCount > 0) {
            $tracker->increment('media_messages_count', $mediaCount);
        }
        
        $tracker->update([
            'last_message_at' => now(),
            'last_overage_payment_at' => now(),
        ]);
    }
}
