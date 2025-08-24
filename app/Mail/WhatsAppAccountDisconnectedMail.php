<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\WhatsAppAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class WhatsAppAccountDisconnectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public WhatsAppAccount $account
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Déconnexion de votre compte WhatsApp - ' . $this->account->session_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.whatsapp-account-disconnected',
        );
    }
}