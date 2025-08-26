<?php

namespace App\Listeners;

use App\Mail\ExternalTransactionApprovedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendApprovalNotificationListener extends BaseListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected function getEventIdentifiers($event): array
    {
        return [
            'transaction_id' => $event->transaction->id,
            'user_id' => $event->transaction->wallet->user->id,
            'event_type' => 'external_transaction_approved',
        ];
    }

    protected function handleEvent($event): void
    {
        $user = $event->transaction->wallet->user;
        Mail::to($user)->send(new ExternalTransactionApprovedMail($event->transaction));
    }
}
