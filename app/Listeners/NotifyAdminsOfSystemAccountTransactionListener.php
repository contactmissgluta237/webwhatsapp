<?php

namespace App\Listeners;

use App\Mail\SystemAccountTransactionNotificationMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class NotifyAdminsOfSystemAccountTransactionListener extends BaseListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        // Retrieve all active administrators
    }

    protected function getEventIdentifiers($event): array
    {
        return [
            'system_transaction_id' => $event->systemAccountTransaction->id,
            'event_type' => 'system_account_transaction_created',
        ];
    }

    /**
     * Handle the event.
     */
    protected function handleEvent($event): void
    {
        $systemAccountTransaction = $event->systemAccountTransaction;

        $adminEmails = config('admin.emails');

        if (empty($adminEmails)) {
            $adminUsers = User::whereHas('roles', function (Builder $query): void {
                $query->where('name', 'admin');
            })->get();
            $adminEmails = $adminUsers->pluck('email')->toArray();
        }

        if (! empty($adminEmails)) {
            Mail::to($adminEmails)->send(new SystemAccountTransactionNotificationMail($systemAccountTransaction));
        }
    }
}
