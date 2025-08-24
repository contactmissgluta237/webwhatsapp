<?php

declare(strict_types=1);

namespace App\Listeners\WhatsApp;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\WhatsApp\MessageProcessedEvent;
use App\Models\InternalTransaction;
use App\Models\WhatsAppAccountUsage;
use App\Services\WhatsApp\Helpers\MessageCostHelper;
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
        if (! $event->wasSuccessful()) {
            return;
        }

        $user = $event->account->user;
        $subscription = $user->activeSubscription;

        if (! $subscription) {
            Log::warning('[BILLING_COUNTER] No active subscription found', [
                'user_id' => $user->id,
                'session_id' => $event->getSessionId(),
            ]);

            return;
        }

        $accountUsage = $subscription->getUsageForAccount($event->account);

        // Calculer le coût du message basé sur les produits dans la réponse
        $products = collect($event->aiResponse->products ?? []);
        $messageCost = MessageCostHelper::calculateMessageCost($products);

        DB::transaction(function () use ($subscription, $accountUsage, $messageCost, $user, $event, $products) {
            $this->processMessageBilling($subscription, $accountUsage, $messageCost, $user, $event, $products);
        });
    }

    private function processMessageBilling(
        $subscription,
        WhatsAppAccountUsage $accountUsage,
        int $messageCost,
        $user,
        MessageProcessedEvent $event,
        $products
    ): void {
        if ($subscription->hasRemainingMessages()) {
            // Cas normal : utiliser le quota
            $this->processNormalQuota($accountUsage, $messageCost, $products);

            Log::debug('[BILLING_COUNTER] Used normal quota', [
                'user_id' => $user->id,
                'message_cost' => $messageCost,
                'remaining_after' => $subscription->getRemainingMessages(),
            ]);
        } else {
            // Cas dépassement : débiter le wallet
            $this->processOverageBilling($accountUsage, $messageCost, $user, $products);

            Log::debug('[BILLING_COUNTER] Processed overage billing', [
                'user_id' => $user->id,
                'message_cost' => $messageCost,
                'overage_total' => $accountUsage->overage_messages_used,
            ]);
        }
    }

    private function processNormalQuota(
        WhatsAppAccountUsage $accountUsage,
        int $messageCost,
        $products
    ): void {
        $accountUsage->incrementUsage($messageCost);

        $mediaCount = MessageCostHelper::getProductsMediaCount($products);
        if ($mediaCount > 0) {
            $accountUsage->incrementMediaUsage($mediaCount);
        }
    }

    private function processOverageBilling(
        WhatsAppAccountUsage $accountUsage,
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
            'related_type' => WhatsAppAccountUsage::class,
            'related_id' => $accountUsage->id,
            'created_by' => $user->id,
            'completed_at' => now(),
        ]);

        // Mettre à jour le solde du wallet
        $user->wallet->decrement('balance', $overageCostXAF);

        // Tracker les statistiques de dépassement
        $accountUsage->incrementOverageUsage($messageCost, $overageCostXAF);

        $mediaCount = MessageCostHelper::getProductsMediaCount($products);
        if ($mediaCount > 0) {
            $accountUsage->incrementMediaUsage($mediaCount);
        }
    }
}
