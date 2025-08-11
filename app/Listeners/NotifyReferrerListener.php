<?php

namespace App\Listeners;

use App\Events\CustomerCreatedEvent;
use App\Mail\ReferralNotificationMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class NotifyReferrerListener implements ShouldQueue
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
    public function handle(CustomerCreatedEvent $event): void
    {
        $customer = $event->customer;

        if ($customer->user->referrer) {
            $referrerUser = $customer->user->referrer;

            if ($referrerUser instanceof User && $referrerUser->email) {
                Mail::to($referrerUser->email)->send(
                    new ReferralNotificationMail($referrerUser, $customer->user)
                );
            }
        }
    }
}
