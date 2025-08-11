<?php

namespace App\Listeners;

use App\Events\ExternalTransactionApprovedEvent;
use App\Mail\ExternalTransactionApprovedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendApprovalNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ExternalTransactionApprovedEvent $event): void
    {
        $user = $event->transaction->wallet->user;
        Mail::to($user)->send(new ExternalTransactionApprovedMail($event->transaction));
    }
}
