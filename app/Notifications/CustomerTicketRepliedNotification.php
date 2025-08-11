<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\TicketReplyNotificationMail;
use App\Models\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

final class CustomerTicketRepliedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TicketMessage $ticketMessage
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): TicketReplyNotificationMail
    {
        $ticket = $this->ticketMessage->ticket;

        return (new TicketReplyNotificationMail($this->ticketMessage, $notifiable))
            ->to($notifiable->email)
            ->with(['ticketUrl' => route('customer.tickets.show', $ticket->id)]);
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $ticket = $this->ticketMessage->ticket;

        return new DatabaseMessage([
            'title' => 'Nouvelle réponse de l\'administrateur sur votre ticket',
            'message' => 'L\'administrateur a répondu à votre ticket #'.$ticket->ticket_number.'.',
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'message_id' => $this->ticketMessage->id,
            'url' => route('customer.tickets.show', $ticket->id),
        ]);
    }
}
