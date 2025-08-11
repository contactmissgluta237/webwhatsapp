<?php

namespace App\Listeners;

use App\Enums\TicketSenderType;
use App\Events\TicketMessageSentEvent;
use App\Notifications\CustomerTicketRepliedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyCustomerOfTicketReplyListener implements ShouldQueue
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
        if ($event->ticketMessage->sender_type->equals(TicketSenderType::ADMIN())) {
            $event->ticketMessage->ticket->user->notify(new CustomerTicketRepliedNotification($event->ticketMessage));
        }
    }
}
