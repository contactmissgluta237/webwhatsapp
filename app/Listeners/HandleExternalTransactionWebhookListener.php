<?php

namespace App\Listeners;

use App\Enums\TransactionStatus;
use App\Events\ExternalTransactionWebhookProcessedEvent;
use App\Mail\AdminInitiatedWithdrawalNotificationMail;
use App\Mail\RechargeNotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class HandleExternalTransactionWebhookListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ExternalTransactionWebhookProcessedEvent $event): void
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
                $customer->wallet->increment('balance', $transaction->amount);
                Log::info('Wallet balance incremented for recharge', [
                    'user_id' => $customer->id,
                    'amount' => $transaction->amount,
                    'new_balance' => $customer->wallet->balance,
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
            // TODO: Handle failed/cancelled transactions (e.g., refund if necessary, notify admin/customer)
            Log::warning('External transaction failed or cancelled via webhook', [
                'transaction_id' => $transaction->id,
                'status' => $transaction->status->value,
            ]);
        }
    }
}
