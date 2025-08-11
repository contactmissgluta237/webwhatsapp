<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $otp,
        public string $maskedIdentifier,
        public ?string $resetUrl = null,
        public ?string $userName = null,
        public int $validityMinutes = 10,
        public string $verificationType = 'password_reset'
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.otp.title').' - '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.otp',
            with: [
                'otp' => $this->otp,
                'maskedIdentifier' => $this->maskedIdentifier,
                'resetUrl' => $this->resetUrl,
                'userName' => $this->userName,
                'validityMinutes' => $this->validityMinutes,
                'verificationType' => $this->verificationType,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
