<?php

namespace App\Listeners;

use App\Events\TicketCreatedEvent;
use App\Models\User;
use App\Notifications\AdminNewTicketNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class NotifyAdminOfNewTicketListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(TicketCreatedEvent $event): void
    {
        // Récupérer tous les administrateurs
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        // Envoyer un e-mail et une notification à chaque administrateur
        foreach ($admins as $admin) {
            $admin->notify(new AdminNewTicketNotification($event->ticket));
        }
    }
}
