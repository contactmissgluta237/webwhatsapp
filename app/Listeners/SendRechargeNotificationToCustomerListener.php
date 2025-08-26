<?php

namespace App\Listeners;

use App\Mail\RechargeNotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendRechargeNotificationToCustomerListener extends BaseListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    protected function getEventIdentifiers($event): array
    {
        return [
            'transaction_id' => $event->transaction->id,
            'event_type' => 'recharge_completed_by_admin',
        ];
    }

    /**
     * Handle the event.
     */
    protected function handleEvent($event): void
    {
        $transaction = $event->transaction;
        $customer = $transaction->wallet->user;

        if ($customer && $customer->email) {
            Mail::to($customer->email)->send(new RechargeNotificationMail($transaction));
        }
    }
}
