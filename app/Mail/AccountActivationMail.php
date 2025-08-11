<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountActivationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $otp,
        public string $maskedIdentifier,
        public string $activationUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Activation de votre compte',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.account-activation',
            with: [
                'otp' => $this->otp,
                'maskedIdentifier' => $this->maskedIdentifier,
                'activationUrl' => $this->activationUrl,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
