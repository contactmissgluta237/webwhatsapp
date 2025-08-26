<?php

namespace App\Listeners;

use App\Enums\TransactionStatus;
use App\Mail\AdminInitiatedWithdrawalNotificationMail;
use App\Mail\RechargeNotificationMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class HandleExternalTransactionWebhookListener extends BaseListener
{
    protected function getEventIdentifiers($event): array
    {
        return [
            'transaction_id' => $event->transaction->id,
            'webhook_event_type' => 'external_transaction',
            'status' => $event->transaction->status->value,
        ];
    }

    /**
     * Handle the event.
     */
    protected function handleEvent($event): void
    {
        $transaction = $event->transaction;
        $customer = $transaction->wallet->user;

        Log::info('Handling ExternalTransactionWebhookProcessedEvent', [
            'transaction_id' => $transaction->id,
            'status' => $transaction->status->value,
            'type' => $transaction->transaction_type->value,
        ]);

        if ($transaction->status->equals(TransactionStatus::COMPLETED())) {
            if ($transaction->isRecharge()) {
                $transaction->wallet->increment('balance', $transaction->amount);
                Log::info('Wallet balance incremented for recharge', [
                    'user_id' => $customer->id,
                    'amount' => $transaction->amount,
                    'new_balance' => $transaction->wallet->balance,
                ]);
                if ($customer->email) {
                    Mail::to($customer->email)->send(new RechargeNotificationMail($transaction));
                }
            } elseif ($transaction->isWithdrawal()) {
                // For withdrawals, balance is decremented at initiation if manual, or at approval if pending
                // For automatic withdrawals, balance is decremented at initiation
                // No need to decrement here again if it's already done.
                // We only send notification here.
                if ($customer->email) {
                    Mail::to($customer->email)->send(new AdminInitiatedWithdrawalNotificationMail($transaction));
                }
            }
        } elseif ($transaction->status->equals(TransactionStatus::FAILED()) || $transaction->status->equals(TransactionStatus::CANCELLED())) {
            Log::warning('External transaction failed or cancelled via webhook', [
                'transaction_id' => $transaction->id,
                'status' => $transaction->status->value,
            ]);
        }
    }
}
