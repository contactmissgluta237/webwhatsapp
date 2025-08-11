<?php

namespace App\Listeners;

use App\Events\AdminWithdrawalCreatedEvent;
use App\Mail\AdminInitiatedWithdrawalNotificationMail;
use App\Mail\AdminWithdrawalNotificationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AdminWithdrawalNotificationListener
{
    public function handle(AdminWithdrawalCreatedEvent $event): void
    {
        $transaction = $event->transaction;
        $customer = $transaction->wallet->user;

        if ($customer->email) {
            Mail::to($customer->email)->send(new AdminInitiatedWithdrawalNotificationMail($transaction));
        }

        $adminEmails = config('admin.emails');

        if (empty($adminEmails)) {
            $adminUsers = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->get();
            $adminEmails = $adminUsers->pluck('email')->toArray();
        }

        if (! empty($adminEmails)) {
            Mail::to($adminEmails)->send(new AdminWithdrawalNotificationMail($customer, $transaction));
        }
    }
}
