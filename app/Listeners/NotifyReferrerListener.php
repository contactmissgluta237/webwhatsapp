<?php

namespace App\Listeners;

use App\Mail\ReferralNotificationMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class NotifyReferrerListener extends BaseListener implements ShouldQueue
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
            'customer_id' => $event->customer->id,
            'referrer_id' => $event->customer->user->referrer?->id,
            'event_type' => 'customer_created_referral',
        ];
    }

    /**
     * Handle the event.
     */
    protected function handleEvent($event): void
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
