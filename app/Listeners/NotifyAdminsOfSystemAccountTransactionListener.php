<?php

namespace App\Listeners;

use App\Events\SystemAccountTransactionCreatedEvent;
use App\Mail\SystemAccountTransactionNotificationMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class NotifyAdminsOfSystemAccountTransactionListener implements ShouldQueue
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
    public function handle(SystemAccountTransactionCreatedEvent $event): void
    {
        $systemAccountTransaction = $event->systemAccountTransaction;

        $adminEmails = config('admin.emails');

        if (empty($adminEmails)) {
            $adminUsers = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->get();
            $adminEmails = $adminUsers->pluck('email')->toArray();
        }

        if (! empty($adminEmails)) {
            Mail::to($adminEmails)->send(new SystemAccountTransactionNotificationMail($systemAccountTransaction));
        }
    }
}
