<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Mail\WithdrawalRequestedMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
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
        $admins = User::whereHas('roles', function (Builder $query): void {
            $query->where('name', UserRole::ADMIN()->value);
        })->get();

        foreach ($admins as $admin) {
            Mail::to($admin)->send(new WithdrawalRequestedMail($event->transaction));
        }
    }
}
