<?php

namespace App\Mail;

use App\Models\ExternalTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminInitiatedWithdrawalNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ExternalTransaction $transaction
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Retrait initié par un administrateur - '.number_format($this->transaction->amount, 0, ',', ' ').' FCFA',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-initiated-withdrawal-notification',
            with: [
                'transaction' => $this->transaction,
                'customer' => $this->transaction->wallet->user,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
