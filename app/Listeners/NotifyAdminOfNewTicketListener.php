<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\AdminNewTicketNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class NotifyAdminOfNewTicketListener extends BaseListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct() {}

    protected function getEventIdentifiers($event): array
    {
        return [
            'ticket_id' => $event->ticket->id,
            'user_id' => $event->ticket->user->id,
            'event_type' => 'ticket_created',
        ];
    }

    /**
     * Handle the event.
     */
    protected function handleEvent($event): void
    {
        // Récupérer tous les administrateurs
        $admins = User::whereHas('roles', function (Builder $query): void {
            $query->where('name', 'admin');
        })->get();

        // Envoyer un e-mail et une notification à chaque administrateur
        foreach ($admins as $admin) {
            $admin->notify(new AdminNewTicketNotification($event->ticket));
        }
    }
}
