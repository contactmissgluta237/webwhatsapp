<?php

namespace App\Listeners;

use App\Events\RechargeCompletedByAdminEvent;
use App\Mail\RechargeNotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendRechargeNotificationToCustomerListener implements ShouldQueue
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
    public function handle(RechargeCompletedByAdminEvent $event): void
    {
        $transaction = $event->transaction;
        $customer = $transaction->wallet->user;

        if ($customer && $customer->email) {
            Mail::to($customer->email)->send(new RechargeNotificationMail($transaction));
        }
    }
}
