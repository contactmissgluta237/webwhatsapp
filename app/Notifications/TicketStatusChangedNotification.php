<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\TicketStatusChangeNotificationMail;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

final class TicketStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): TicketStatusChangeNotificationMail
    {
        return (new TicketStatusChangeNotificationMail($this->ticket))
            ->to($notifiable->email)
            ->with(['ticketUrl' => route('customer.tickets.show', $this->ticket->id)]);
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Statut de votre ticket mis à jour',
            'message' => 'Le statut de votre ticket #'.$this->ticket->ticket_number.' a été mis à jour à '.$this->ticket->status->label.'.',
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'url' => route('customer.tickets.show', $this->ticket->id),
        ]);
    }
}
