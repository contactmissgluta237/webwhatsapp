<?php

namespace App\Mail;

use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketReplyNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public TicketMessage $ticketMessage;
    public User $recipient;

    /**
     * Create a new message instance.
     */
    public function __construct(TicketMessage $ticketMessage, User $recipient)
    {
        $this->ticketMessage = $ticketMessage;
        $this->recipient = $recipient;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->recipient->email,
            subject: 'Ticket Reply Notification',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tickets.ticket-reply-notification',
            with: [
                'ticketMessage' => $this->ticketMessage,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
