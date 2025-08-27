<?php

declare(strict_types=1);

namespace App\Listeners\WhatsApp;

use App\Events\WhatsApp\MessageProcessedEvent;
use App\Listeners\BaseListener;
use App\Notifications\WhatsApp\LowQuotaNotification;
use App\Notifications\WhatsApp\WalletDebitedNotification;
use App\Services\WhatsApp\Helpers\MessageBillingHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Simple billing listener: check quota -> debit quota OR debit wallet
 */
final class BillingCounterListener extends BaseListener
{
    /**
     * Extracts unique identifiers for MessageProcessedEvent
     */
    protected function getEventIdentifiers($event): array
    {
        return [
            'account_id' => $event->account->id,
            'message_id' => $event->incomingMessage->id,
            'session_id' => $event->aiResponse->sessionId,
            'ai_response' => $event->aiResponse->aiResponse ?? '',
        ];
    }

    /**
     * Handles the billing event
     */
    protected function handleEvent($event): void
    {
        if (! $event->wasSuccessful()) {
            return;
        }

        $user = $event->account->user;
        $subscription = $user->activeSubscription;

        if (! $subscription) {
            Log::warning('[BillingCounterListener] No active subscription - attempting direct wallet debit', [
                'user_id' => $user->id,
                'session_id' => $event->getSessionId(),
            ]);

            // No subscription: debit wallet directly using existing billing helper
            $this->debitWalletDirectly($user, $event);

            return;
        }

        try {
            DB::transaction(function () use ($subscription, $event, $user) {
                // Get accountUsage for this specific account
                $accountUsage = $subscription->getUsageForAccount($event->account);

                // Calculate message count from response
                $messageCount = MessageBillingHelper::getNumberOfMessagesFromResponse($event->aiResponse);

                Log::info('[BillingCounterListener] Processing billing', [
                    'user_id' => $user->id,
                    'session_id' => $event->getSessionId(),
                    'message_count' => $messageCount,
                    'remaining_messages_before' => $subscription->getRemainingMessages(),
                ]);

                // Check if subscription has remaining messages
                if ($subscription->hasRemainingMessages($messageCount)) {
                    // Increment messages_used for this account and update timestamp
                    $accountUsage->increment('messages_used', $messageCount);
                    $accountUsage->update(['last_message_at' => now()]);

                    Log::info('[BillingCounterListener] Used quota', [
                        'user_id' => $user->id,
                        'messages_used' => $messageCount,
                        'account_usage_id' => $accountUsage->id,
                    ]);

                    // Fresh subscription to get updated remaining count
                    $subscription = $subscription->fresh();

                    // Check if we should send low quota alert
                    if ($subscription->shouldSendLowQuotaAlert()) {
                        $remainingMessages = $subscription->getRemainingMessages();

                        $user->notify(new LowQuotaNotification($subscription, $remainingMessages));

                        Log::info('[BillingCounterListener] Low quota notification sent', [
                            'user_id' => $user->id,
                            'remaining_messages' => $remainingMessages,
                        ]);
                    }

                } else {
                    // No quota remaining: debit wallet
                    $billingAmount = MessageBillingHelper::getAmountToBillFromResponse($event->aiResponse);

                    if ($accountUsage->debitWalletForOverage($billingAmount)) {
                        // Update timestamps for overage
                        $accountUsage->update([
                            'last_message_at' => now(),
                            'last_overage_payment_at' => now(),
                        ]);

                        $newBalance = (float) $user->wallet->fresh()->balance;

                        // Send wallet debited notification
                        $user->notify(new WalletDebitedNotification($billingAmount, $newBalance));

                        Log::info('[BillingCounterListener] Wallet debited and notification sent', [
                            'user_id' => $user->id,
                            'amount_debited' => $billingAmount,
                            'new_balance' => $newBalance,
                        ]);
                    } else {
                        Log::error('[BillingCounterListener] Failed to debit wallet - insufficient funds', [
                            'user_id' => $user->id,
                            'required_amount' => $billingAmount,
                            'wallet_balance' => $user->wallet?->balance ?? 0,
                        ]);
                    }
                }
            });

        } catch (\Exception $e) {
            Log::error('[BillingCounterListener] Billing processing failed', [
                'user_id' => $user->id,
                'session_id' => $event->getSessionId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Debit wallet directly when no subscription exists
     * Uses the same MessageBillingHelper functions as the overage system
     */
    private function debitWalletDirectly($user, $event): void
    {
        try {
            DB::transaction(function () use ($user, $event) {
                // Use the same billing helper as the overage system
                $billingAmount = MessageBillingHelper::getAmountToBillFromResponse($event->aiResponse);

                Log::info('[BillingCounterListener] Direct wallet debit calculation', [
                    'user_id' => $user->id,
                    'billing_amount' => $billingAmount,
                    'session_id' => $event->getSessionId(),
                ]);

                if (! $user->wallet) {
                    Log::error('[BillingCounterListener] No wallet found for direct debit', [
                        'user_id' => $user->id,
                        'required_amount' => $billingAmount,
                    ]);

                    return;
                }

                if ($user->wallet->balance < $billingAmount) {
                    Log::error('[BillingCounterListener] Insufficient wallet balance for direct debit', [
                        'user_id' => $user->id,
                        'required_amount' => $billingAmount,
                        'wallet_balance' => $user->wallet->balance,
                    ]);

                    return;
                }

                // Debit wallet directly (same logic as WhatsAppAccountUsage::debitWalletForOverage)
                $newBalance = max(0, $user->wallet->balance - $billingAmount);
                $user->wallet->update(['balance' => $newBalance]);

                // Send notification (same as overage system)
                $user->notify(new WalletDebitedNotification($billingAmount, $newBalance));

                Log::info('[BillingCounterListener] Direct wallet debit successful', [
                    'user_id' => $user->id,
                    'amount_debited' => $billingAmount,
                    'new_balance' => $newBalance,
                    'session_id' => $event->getSessionId(),
                ]);
            });

        } catch (\Exception $e) {
            Log::error('[BillingCounterListener] Direct wallet debit failed', [
                'user_id' => $user->id,
                'session_id' => $event->getSessionId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
