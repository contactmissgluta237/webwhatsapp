<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\AdminNewTicketNotificationMail;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

final class AdminNewTicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): AdminNewTicketNotificationMail
    {
        return (new AdminNewTicketNotificationMail($this->ticket, $notifiable->email))
            ->to($notifiable->email)
            ->with(['ticketUrl' => route('admin.tickets.show', $this->ticket->id)]);
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Nouveau ticket client créé',
            'message' => 'Un nouveau ticket #'.$this->ticket->ticket_number.' a été créé par '.$this->ticket->user->full_name.'.',
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'url' => route('admin.tickets.show', $this->ticket->id),
        ]);
    }
}
