<?php

declare(strict_types=1);

namespace App\Mail\WhatsApp;

use App\Models\UserSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class LowQuotaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly UserSubscription $subscription,
        private readonly int $remainingMessages
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Quota WhatsApp bientôt épuisé - '.config('app.name'),
        );
    }

    public function content(): Content
    {
        $alertThreshold = config('whatsapp.billing.alert_threshold_percentage', 20);

        return new Content(
            markdown: 'emails.whatsapp.low-quota',
            with: [
                'appName' => config('app.name'),
                'subscription' => $this->subscription,
                'remainingMessages' => $this->remainingMessages,
                'totalMessages' => $this->subscription->messages_limit,
                'alertThreshold' => $alertThreshold,
                'rechargeUrl' => url('/customer/recharge'),
            ]
        );
    }
}
