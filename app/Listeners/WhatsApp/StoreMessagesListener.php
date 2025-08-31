<?php

declare(strict_types=1);

namespace App\Listeners\WhatsApp;

use App\DTOs\WhatsApp\MessageExchangeResult;
use App\Enums\BillingType;
use App\Events\WhatsApp\MessageProcessedEvent;
use App\Listeners\BaseListener;
use App\Models\MessageUsageLog;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\WhatsAppAccountUsage;
use App\Notifications\WhatsApp\LowQuotaNotification;
use App\Notifications\WhatsApp\WalletDebitedNotification;
use App\Repositories\WhatsApp\Contracts\WhatsAppMessageRepositoryInterface;
use App\Services\WhatsApp\Helpers\MessageBillingHelper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class StoreMessagesListener extends BaseListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly WhatsAppMessageRepositoryInterface $messageRepository
    ) {}

    /**
     * @param  MessageProcessedEvent  $event
     */
    protected function getEventIdentifiers($event): array
    {
        return [
            'account_id' => $event->account->id,
            'message_id' => $event->incomingMessage->id,
            'session_id' => $event->getSessionId(),
            'from_phone' => $event->getFromPhone(),
        ];
    }

    /**
     * @param  MessageProcessedEvent  $event
     */
    protected function handleEvent($event): void
    {
        Log::info('[STORE_MESSAGES] Starting message processing', [
            'session_id' => $event->getSessionId(),
            'from_phone' => $event->getFromPhone(),
            'message_id' => $event->incomingMessage->id,
            'user_id' => $event->account->user->id,
            'has_ai_response' => $event->aiResponse->hasAiResponse,
            'product_count' => count($event->aiResponse->products),
        ]);

        if (! $event->wasSuccessful()) {
            Log::warning('[STORE_MESSAGES] Event was not successful, skipping processing', [
                'session_id' => $event->getSessionId(),
                'was_successful' => $event->aiResponse->wasSuccessful(),
                'processing_error' => $event->aiResponse->processingError,
                'processed' => $event->aiResponse->processed,
            ]);

            return;
        }

        try {
            Log::debug('[STORE_MESSAGES] Storing message exchange', [
                'session_id' => $event->getSessionId(),
                'account_id' => $event->account->id,
            ]);

            $result = $this->messageRepository->storeMessageExchange(
                $event->account,
                $event->incomingMessage,
                $event->aiResponse
            );

            Log::debug('[STORE_MESSAGES] Message exchange stored successfully', [
                'session_id' => $event->getSessionId(),
                'conversation_id' => $result->conversation->id,
                'outgoing_message_id' => $result->outgoingMessage?->id,
            ]);

            $this->handleBilling($event, $result);

            Log::info('[STORE_MESSAGES] Message stored and billed successfully', [
                'session_id' => $event->getSessionId(),
                'conversation_id' => $result->conversation->id,
                'user_id' => $event->account->user->id,
                'outgoing_message_id' => $result->outgoingMessage?->id,
            ]);

        } catch (\Exception $e) {
            Log::error('[STORE_MESSAGES] Failed to process message', [
                'session_id' => $event->getSessionId(),
                'user_id' => $event->account->user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function handleBilling(MessageProcessedEvent $event, MessageExchangeResult $result): void
    {
        Log::debug('[STORE_MESSAGES] Starting billing process', [
            'session_id' => $event->getSessionId(),
            'user_id' => $event->account->user->id,
        ]);

        $user = $event->account->user->fresh();
        $subscription = $user->activeSubscription;

        Log::debug('[STORE_MESSAGES] Billing context retrieved', [
            'session_id' => $event->getSessionId(),
            'user_id' => $user->id,
            'has_subscription' => $subscription !== null,
            'subscription_id' => $subscription?->id,
            'remaining_messages' => $subscription?->getRemainingMessages(),
            'wallet_balance' => $user->wallet?->balance,
        ]);

        if (! $subscription || ! $subscription->hasRemainingMessages()) {
            Log::info('[STORE_MESSAGES] No subscription or no remaining messages, using wallet billing', [
                'session_id' => $event->getSessionId(),
                'user_id' => $user->id,
                'has_subscription' => $subscription !== null,
                'remaining_messages' => $subscription?->getRemainingMessages() ?? 0,
            ]);
            $this->handleWalletBilling($event, $result);
        } else {
            Log::info('[STORE_MESSAGES] Using subscription quota billing', [
                'session_id' => $event->getSessionId(),
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'remaining_messages' => $subscription->getRemainingMessages(),
            ]);
            $this->handleSubscriptionBilling($event, $result, $subscription, $user);
        }
    }

    private function handleSubscriptionBilling(MessageProcessedEvent $event, MessageExchangeResult $result, UserSubscription $subscription, User $user): void
    {
        Log::debug('[STORE_MESSAGES] Processing subscription billing', [
            'session_id' => $event->getSessionId(),
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
        ]);

        $this->createUsageLog($event, $result);

        Log::debug('[STORE_MESSAGES] Checking for low quota notifications', [
            'session_id' => $event->getSessionId(),
            'user_id' => $user->id,
            'remaining_messages_after' => $subscription->fresh()->getRemainingMessages(),
        ]);

        $this->sendLowQuotaNotificationIfNeeded($subscription->fresh(), $user);

        Log::info('[STORE_MESSAGES] Subscription billing completed', [
            'session_id' => $event->getSessionId(),
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
        ]);
    }

    private function handleWalletBilling(MessageProcessedEvent $event, MessageExchangeResult $result): void
    {
        Log::debug('[STORE_MESSAGES] Processing wallet billing', [
            'session_id' => $event->getSessionId(),
            'user_id' => $event->account->user->id,
        ]);

        $this->createUsageLog($event, $result);

        Log::info('[STORE_MESSAGES] Wallet billing completed', [
            'session_id' => $event->getSessionId(),
            'user_id' => $event->account->user->id,
        ]);
    }

    private function createUsageLog(MessageProcessedEvent $event, MessageExchangeResult $result): void
    {
        Log::debug('[STORE_MESSAGES] Creating usage log', [
            'session_id' => $event->getSessionId(),
            'user_id' => $event->account->user->id,
            'has_outgoing_message' => $result->outgoingMessage !== null,
        ]);

        if (! $result->outgoingMessage) {
            Log::warning('[STORE_MESSAGES] No outgoing message found, skipping usage log creation', [
                'session_id' => $event->getSessionId(),
                'user_id' => $event->account->user->id,
            ]);

            return;
        }

        $user = $event->account->user->fresh();
        $subscription = $user->activeSubscription;

        // Calculate costs with new formula
        $aiCost = ! empty($event->aiResponse->aiResponse) ? config('whatsapp.billing.costs.ai_message', 15) : 0;
        $productCount = MessageBillingHelper::getNumberOfProductsFromResponse($event->aiResponse);
        $productCost = $productCount * config('whatsapp.billing.costs.product_message', 10);
        $totalCost = $aiCost + $productCost;

        Log::info('[STORE_MESSAGES] Billing calculation completed', [
            'session_id' => $event->getSessionId(),
            'user_id' => $user->id,
            'ai_cost' => $aiCost,
            'product_count' => $productCount,
            'product_cost' => $productCost,
            'total_cost' => $totalCost,
            'formula' => 'NEW: 1 IA + products only (no media)',
        ]);

        // Calculate number of messages to debit based on new formula
        $messagesToDebit = MessageBillingHelper::getNumberOfMessagesFromResponse($event->aiResponse);

        if ($subscription && $subscription->hasRemainingMessages($messagesToDebit)) {
            Log::debug('[STORE_MESSAGES] Using subscription quota', [
                'session_id' => $event->getSessionId(),
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'remaining_before' => $subscription->getRemainingMessages(),
                'messages_to_debit' => $messagesToDebit,
            ]);

            $accountUsage = WhatsAppAccountUsage::getOrCreateForAccount($subscription, $event->account);
            $billingType = BillingType::SUBSCRIPTION_QUOTA;

            Log::info('[STORE_MESSAGES] Quota billing applied', [
                'session_id' => $event->getSessionId(),
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'account_usage_id' => $accountUsage->id,
                'messages_debited' => $messagesToDebit,
            ]);
        } else {
            Log::debug('[STORE_MESSAGES] Attempting wallet debit', [
                'session_id' => $event->getSessionId(),
                'user_id' => $user->id,
                'required_amount' => $totalCost,
                'wallet_balance' => $user->wallet?->balance ?? 0,
            ]);

            if (! $user->wallet || $user->wallet->balance < $totalCost) {
                Log::error('[STORE_MESSAGES] Insufficient wallet balance - billing failed', [
                    'session_id' => $event->getSessionId(),
                    'user_id' => $user->id,
                    'required' => $totalCost,
                    'available' => $user->wallet?->balance ?? 0,
                    'shortfall' => $totalCost - ($user->wallet?->balance ?? 0),
                ]);

                return;
            }

            $oldBalance = $user->wallet->balance;
            $newBalance = max(0, $oldBalance - $totalCost);
            $user->wallet->update(['balance' => $newBalance]);
            $accountUsage = WhatsAppAccountUsage::getOrCreateWalletOnlyUsage($event->account);
            $billingType = BillingType::WALLET_DIRECT;

            Log::info('[STORE_MESSAGES] Wallet debited successfully', [
                'session_id' => $event->getSessionId(),
                'user_id' => $user->id,
                'amount_debited' => $totalCost,
                'balance_before' => $oldBalance,
                'balance_after' => $newBalance,
                'account_usage_id' => $accountUsage->id,
            ]);

            // Notify user of wallet debit
            $user->notify(new WalletDebitedNotification($totalCost, $newBalance));

            Log::debug('[STORE_MESSAGES] Wallet debit notification sent', [
                'session_id' => $event->getSessionId(),
                'user_id' => $user->id,
                'amount' => $totalCost,
                'new_balance' => $newBalance,
            ]);
        }

        // Create multiple usage logs to properly reflect the message debit according to new formula
        $messagesToDebit = MessageBillingHelper::getNumberOfMessagesFromResponse($event->aiResponse);
        $createdLogs = [];

        // Distribute costs across the debited messages
        $costPerMessage = $messagesToDebit > 0 ? $totalCost / $messagesToDebit : 0;

        for ($i = 0; $i < $messagesToDebit; $i++) {
            $isFirstMessage = $i === 0;

            $usageLog = MessageUsageLog::create([
                'whatsapp_message_id' => $result->outgoingMessage->id,
                'whatsapp_account_usage_id' => $accountUsage?->id,
                'whatsapp_conversation_id' => $result->conversation->id,
                'user_id' => $user->id,
                'ai_message_cost' => $isFirstMessage ? $aiCost : 0, // Only count AI cost on first message
                'product_count' => $isFirstMessage ? $productCount : 0, // Only count products on first message
                'product_cost' => $isFirstMessage ? $productCost : 0, // Only count product cost on first message
                'total_cost' => $costPerMessage,
                'billing_type' => $billingType,
            ]);

            $createdLogs[] = $usageLog->id;
        }

        Log::info('[STORE_MESSAGES] Usage logs created successfully', [
            'session_id' => $event->getSessionId(),
            'user_id' => $user->id,
            'usage_log_ids' => $createdLogs,
            'logs_created' => count($createdLogs),
            'messages_debited' => $messagesToDebit,
            'outgoing_message_id' => $result->outgoingMessage->id,
            'billing_type' => $billingType->value,
            'total_cost' => $totalCost,
            'cost_per_message' => $costPerMessage,
        ]);
    }

    private function sendLowQuotaNotificationIfNeeded(UserSubscription $subscription, User $user): void
    {
        $remaining = $subscription->getRemainingMessages();
        $shouldAlert = $subscription->shouldSendLowQuotaAlert();

        Log::debug('[STORE_MESSAGES] Checking low quota alert conditions', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'remaining_messages' => $remaining,
            'should_send_alert' => $shouldAlert,
            'alert_threshold' => config('whatsapp.billing.alert_threshold_percentage', 20),
        ]);

        if ($shouldAlert) {
            Log::info('[STORE_MESSAGES] Sending low quota notification', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'remaining_messages' => $remaining,
            ]);

            $user->notify(new LowQuotaNotification($subscription, $remaining));

            Log::debug('[STORE_MESSAGES] Low quota notification sent successfully', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'remaining_messages' => $remaining,
            ]);
        } else {
            Log::debug('[STORE_MESSAGES] No low quota notification needed', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'remaining_messages' => $remaining,
            ]);
        }
    }
}
