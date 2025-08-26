<?php

namespace App\Listeners;

use App\Enums\TicketSenderType;
use App\Notifications\CustomerTicketRepliedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyCustomerOfTicketReplyListener extends BaseListener implements ShouldQueue
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
            'event_type' => 'ticket_customer_reply_notify',
        ];
    }

    /**
     * Handle the event.
     */
    protected function handleEvent($event): void
    {
        if ($event->ticketMessage->sender_type->equals(TicketSenderType::ADMIN())) {
            $event->ticketMessage->ticket->user->notify(new CustomerTicketRepliedNotification($event->ticketMessage));
        }
    }
}
