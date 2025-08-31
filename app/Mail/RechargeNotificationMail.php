<?php

namespace App\Mail;

use App\Models\ExternalTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RechargeNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ExternalTransaction $transaction
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.recharge_notification.subject', ['amount' => number_format($this->transaction->amount, 0, ',', ' ')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recharge-notification',
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
