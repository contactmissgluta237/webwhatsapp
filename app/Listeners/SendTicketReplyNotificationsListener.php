<?php

namespace App\Listeners;

use App\Enums\TicketSenderType;
use App\Models\User;
use App\Notifications\AdminTicketRepliedNotification;
use App\Notifications\CustomerTicketRepliedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTicketReplyNotificationsListener extends BaseListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct() {}

    protected function getEventIdentifiers($event): array
    {
        return [
            'ticket_message_id' => $event->ticketMessage->id,
            'ticket_id' => $event->ticketMessage->ticket->id,
            'sender_type' => $event->ticketMessage->sender_type->value,
            'event_type' => 'ticket_message_sent',
        ];
    }

    protected function handleEvent($event): void
    {
        $ticketMessage = $event->ticketMessage;
        $ticket = $ticketMessage->ticket;

        /** @var \App\Models\Ticket $ticket */
        if ($ticketMessage->sender_type->equals(TicketSenderType::CUSTOMER())) {
            // Notify admins when customer replies
            $admins = User::whereHas('roles', fn ($query) => $query->where('name', 'admin'))->get();
            foreach ($admins as $admin) {
                /** @var \App\Models\User $admin */
                $admin->notify(new AdminTicketRepliedNotification($ticketMessage));
            }
        } elseif ($ticketMessage->sender_type->equals(TicketSenderType::ADMIN())) {
            // Notify customer when admin replies
            /** @var \App\Models\User $customer */
            $customer = $ticket->user; // The user who created the ticket
            $customer->notify(new CustomerTicketRepliedNotification($ticketMessage));
        }
    }
}
