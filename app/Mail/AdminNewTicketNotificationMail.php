<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class AdminNewTicketNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public string $ticketUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouveau Ticket Client: #'.$this->ticket->ticket_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tickets.admin-new-ticket',
            with: [
                'ticket' => $this->ticket,
                'ticketUrl' => $this->ticketUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
