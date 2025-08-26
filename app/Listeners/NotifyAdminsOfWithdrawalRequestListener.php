<?php

namespace App\Listeners;

use App\Mail\WithdrawalRequestedMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class NotifyAdminsOfWithdrawalRequestListener extends BaseListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected function getEventIdentifiers($event): array
    {
        return [
            'transaction_id' => $event->transaction->id,
            'event_type' => 'withdrawal_requested',
        ];
    }

    protected function handleEvent($event): void
    {
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        foreach ($admins as $admin) {
            Mail::to($admin)->send(new WithdrawalRequestedMail($event->transaction));
        }
    }
}
