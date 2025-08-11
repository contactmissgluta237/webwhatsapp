<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class AdminTicketRepliedNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public TicketMessage $ticketMessage,
        public string $ticketUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvelle réponse sur le ticket #'.$this->ticketMessage->ticket->ticket_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tickets.admin-ticket-replied',
            with: [
                'ticketMessage' => $this->ticketMessage,
                'ticket' => $this->ticketMessage->ticket,
                'ticketUrl' => $this->ticketUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
