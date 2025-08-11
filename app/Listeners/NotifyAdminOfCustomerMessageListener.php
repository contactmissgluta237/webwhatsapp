<?php

namespace App\Listeners;

use App\Enums\TicketSenderType;
use App\Events\TicketMessageSentEvent;
use App\Models\User;
use App\Notifications\AdminTicketRepliedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class NotifyAdminOfCustomerMessageListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(TicketMessageSentEvent $event): void
    {
        if ($event->ticketMessage->sender_type->equals(TicketSenderType::CUSTOMER())) {
            // Récupérer tous les administrateurs
            $admins = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->get();

            // Envoyer la notification à chaque administrateur
            foreach ($admins as $admin) {
                $admin->notify(new AdminTicketRepliedNotification($event->ticketMessage));
            }
        }
    }
}
