<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Notifications\AdminNewTicketSyncNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

final class NotifyAdminOfNewTicketSyncListener extends BaseListener
{
    protected function getEventIdentifiers($event): array
    {
        return [
            'ticket_id' => $event->ticket->id,
            'user_id' => $event->ticket->user->id,
            'event_type' => 'ticket_created_sync',
        ];
    }

    protected function handleEvent($event): void
    {
        try {
            $admins = $this->getAdminUsers();

            if ($admins->isEmpty()) {
                return;
            }

            $this->sendNotificationsToAdmins($admins, $event->ticket);

        } catch (\Exception $e) {
            Log::error('Erreur critique listener ticket sync', [
                'error' => $e->getMessage(),
                'ticket_id' => $event->ticket->id ?? null,
            ]);
        }
    }

    private function getAdminUsers(): Collection
    {
        try {
            return User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->get() ?? new Collection;

        } catch (\Exception $queryException) {
            try {
                return User::where('email', 'LIKE', '%admin%')->get() ?? new Collection;
            } catch (\Exception $fallbackException) {
                return new Collection;
            }
        }
    }

    private function sendNotificationsToAdmins(Collection $admins, $ticket): void
    {
        foreach ($admins as $admin) {
            if (! $admin || ! is_object($admin)) {
                continue;
            }

            if (! method_exists($admin, 'notify')) {
                continue;
            }

            try {
                $notification = new AdminNewTicketSyncNotification($ticket);
                $admin->notify($notification);

            } catch (\Exception $e) {
                Log::error('Erreur notification admin', [
                    'admin_id' => $admin->id ?? 'unknown',
                    'error' => $e->getMessage(),
                    'ticket_id' => $ticket->id,
                ]);
            }
        }
    }
}
