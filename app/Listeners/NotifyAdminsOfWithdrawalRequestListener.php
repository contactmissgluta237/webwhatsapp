<?php

namespace App\Listeners;

use App\Events\WithdrawalRequestedEvent;
use App\Mail\WithdrawalRequestedMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class NotifyAdminsOfWithdrawalRequestListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(WithdrawalRequestedEvent $event): void
    {
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        foreach ($admins as $admin) {
            Mail::to($admin)->send(new WithdrawalRequestedMail($event->transaction));
        }
    }
}
