<?php

namespace App\Mail;

use App\Models\SystemAccountTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SystemAccountTransactionNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public SystemAccountTransaction $systemAccountTransaction;

    /**
     * Create a new message instance.
     */
    public function __construct(SystemAccountTransaction $systemAccountTransaction)
    {
        $this->systemAccountTransaction = $systemAccountTransaction;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $type = $this->systemAccountTransaction->type->label;
        $amount = number_format($this->systemAccountTransaction->amount, 0, ',', ' ');
        $accountType = $this->systemAccountTransaction->systemAccount->type->label;

        return new Envelope(
            subject: "Notification: Transaction de {$type} sur compte {$accountType} - {$amount} FCFA",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.system-account-transaction-notification',
            with: [
                'transaction' => $this->systemAccountTransaction,
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
