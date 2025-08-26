<?php

declare(strict_types=1);

namespace App\Mail\WhatsApp;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class WalletDebitedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly float $debitedAmount,
        private readonly float $newWalletBalance
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '💳 Débit automatique - Quota WhatsApp épuisé - '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.whatsapp.wallet-debited',
            with: [
                'appName' => config('app.name'),
                'debitedAmount' => $this->debitedAmount,
                'newBalance' => $this->newWalletBalance,
                'walletUrl' => url('/customer/wallet'),
                'packagesUrl' => url('/customer/packages'),
            ]
        );
    }
}
