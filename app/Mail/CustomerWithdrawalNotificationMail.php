<?php

namespace App\Mail;

use App\Models\ExternalTransaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerWithdrawalNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $customer,
        public ExternalTransaction $transaction
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Demande de retrait initialisée - '.number_format($this->transaction->amount, 0, ',', ' ').' FCFA',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-withdrawal-notification',
            with: [
                'customer' => $this->customer,
                'transaction' => $this->transaction,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
