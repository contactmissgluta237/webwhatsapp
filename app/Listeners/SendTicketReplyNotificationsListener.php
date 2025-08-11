<?php

namespace App\Listeners;

use App\Enums\TicketSenderType;
use App\Events\TicketMessageSentEvent;
use App\Models\User;
use App\Notifications\AdminTicketRepliedNotification;
use App\Notifications\CustomerTicketRepliedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTicketReplyNotificationsListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct() {}

    public function handle(TicketMessageSentEvent $event): void
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
