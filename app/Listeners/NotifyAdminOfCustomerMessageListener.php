<?php

namespace App\Listeners;

use App\Enums\TicketSenderType;
use App\Models\User;
use App\Notifications\AdminTicketRepliedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class NotifyAdminOfCustomerMessageListener extends BaseListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct() {}

    protected function getEventIdentifiers($event): array
    {
        return [
            'ticket_message_id' => $event->ticketMessage->id,
            'ticket_id' => $event->ticketMessage->ticket->id,
            'sender_type' => $event->ticketMessage->sender_type->value,
            'event_type' => 'ticket_message_admin_notify',
        ];
    }

    /**
     * Handle the event.
     */
    protected function handleEvent($event): void
    {
        if ($event->ticketMessage->sender_type->equals(TicketSenderType::CUSTOMER())) {
            // Do nothing if the message is from the admin
            $admins = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->get();

            // Send notification to each administrator
            foreach ($admins as $admin) {
                $admin->notify(new AdminTicketRepliedNotification($event->ticketMessage));
            }
        }
    }
}
