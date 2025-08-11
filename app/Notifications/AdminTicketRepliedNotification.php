<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\AdminTicketRepliedNotificationMail;
use App\Models\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

final class AdminTicketRepliedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TicketMessage $ticketMessage
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): AdminTicketRepliedNotificationMail
    {
        return (new AdminTicketRepliedNotificationMail($this->ticketMessage, $notifiable->email))
            ->with(['ticketUrl' => route('admin.tickets.show', $this->ticketMessage->ticket->id)]);
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $user = $this->ticketMessage->user;
        $ticket = $this->ticketMessage->ticket;

        return new DatabaseMessage([
            'title' => 'Nouvelle réponse de client sur un ticket',
            'message' => 'Le client '.$user->full_name.' a répondu au ticket #'.$ticket->ticket_number.'.',
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'message_id' => $this->ticketMessage->id,
            'url' => route('admin.tickets.show', $ticket->id),
        ]);
    }
}
