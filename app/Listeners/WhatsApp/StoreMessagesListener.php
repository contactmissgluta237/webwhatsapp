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
use Illuminate\Support\Facades\Log;

final class StoreMessagesListener extends BaseListener
{
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
        if (! $event->wasSuccessful()) {
            return;
        }

        try {
            $result = $this->messageRepository->storeMessageExchange(
                $event->account,
                $event->incomingMessage,
                $event->aiResponse
            );

            $this->handleBilling($event, $result);

            Log::info('[STORE_MESSAGES] Message stored and billed', [
                'session_id' => $event->getSessionId(),
                'conversation_id' => $result->conversation->id,
                'user_id' => $event->account->user->id,
            ]);

        } catch (\Exception $e) {
            Log::error('[STORE_MESSAGES] Failed to process message', [
                'session_id' => $event->getSessionId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handleBilling(MessageProcessedEvent $event, MessageExchangeResult $result): void
    {
        $user = $event->account->user;
        $subscription = $user->activeSubscription;

        if (! $subscription || ! $subscription->hasRemainingMessages()) {
            $this->handleWalletBilling($event, $result);
        } else {
            $this->handleSubscriptionBilling($event, $result, $subscription, $user);
        }
    }

    private function handleSubscriptionBilling(MessageProcessedEvent $event, MessageExchangeResult $result, UserSubscription $subscription, User $user): void
    {
        $this->createUsageLog($event, $result);
        $this->sendLowQuotaNotificationIfNeeded($subscription->fresh(), $user);
    }

    private function handleWalletBilling(MessageProcessedEvent $event, MessageExchangeResult $result): void
    {
        $this->createUsageLog($event, $result);
    }

    private function createUsageLog(MessageProcessedEvent $event, MessageExchangeResult $result): void
    {
        if (! $result->outgoingMessage) {
            return;
        }

        $user = $event->account->user;
        $subscription = $user->activeSubscription;

        $aiCost = ! empty($event->aiResponse->aiResponse) ? config('whatsapp.billing.costs.ai_message', 15) : 0;
        $productCount = MessageBillingHelper::getNumberOfProductsFromResponse($event->aiResponse);
        $productCost = $productCount * config('whatsapp.billing.costs.product_message', 10);
        $mediaCount = MessageBillingHelper::getMediaCountFromResponse($event->aiResponse);
        $mediaCost = $mediaCount * config('whatsapp.billing.costs.media', 5);
        $totalCost = $aiCost + $productCost + $mediaCost;

        if ($subscription && $subscription->hasRemainingMessages()) {
            $accountUsage = WhatsAppAccountUsage::getOrCreateForAccount($subscription, $event->account);
            $billingType = BillingType::SUBSCRIPTION_QUOTA;
        } else {
            if (! $user->wallet || $user->wallet->balance < $totalCost) {
                Log::error('[STORE_MESSAGES] Insufficient wallet balance', [
                    'user_id' => $user->id,
                    'required' => $totalCost,
                    'available' => $user->wallet?->balance ?? 0,
                ]);

                return;
            }

            $newBalance = max(0, $user->wallet->balance - $totalCost);
            $user->wallet->update(['balance' => $newBalance]);
            $accountUsage = WhatsAppAccountUsage::getOrCreateWalletOnlyUsage($event->account);
            $billingType = BillingType::WALLET_DIRECT;

            // Notify user of wallet debit
            $user->notify(new WalletDebitedNotification($totalCost, $newBalance));
        }

        MessageUsageLog::create([
            'whatsapp_message_id' => $result->outgoingMessage->id,
            'whatsapp_account_usage_id' => $accountUsage?->id,
            'whatsapp_conversation_id' => $result->conversation->id,
            'user_id' => $user->id,
            'ai_message_cost' => $aiCost,
            'product_messages_count' => $productCount,
            'product_messages_cost' => $productCost,
            'media_count' => $mediaCount,
            'media_cost' => $mediaCost,
            'total_cost' => $totalCost,
            'billing_type' => $billingType,
        ]);
    }

    private function sendLowQuotaNotificationIfNeeded(UserSubscription $subscription, User $user): void
    {
        if ($subscription->shouldSendLowQuotaAlert()) {
            $remaining = $subscription->getRemainingMessages();
            $user->notify(new LowQuotaNotification($subscription, $remaining));
        }
    }
}
