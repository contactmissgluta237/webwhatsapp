<?php

declare(strict_types=1);

namespace App\Notifications\WhatsApp;

use App\Channels\PushNotificationChannel;
use App\Mail\WhatsApp\WalletDebitedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WalletDebitedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly float $debitedAmount,
        private readonly float $newWalletBalance
    ) {}

    /**
     * Get debited amount for testing
     */
    public function getDebitedAmount(): float
    {
        return $this->debitedAmount;
    }

    /**
     * Get new wallet balance for testing
     */
    public function getNewWalletBalance(): float
    {
        return $this->newWalletBalance;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', PushNotificationChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): WalletDebitedMail
    {
        return (new WalletDebitedMail($this->debitedAmount, $this->newWalletBalance))
            ->to($notifiable->email);
    }

    /**
     * Get the push notification representation.
     */
    public function toPush(object $notifiable): array
    {
        return [
            'title' => 'Wallet débité - WhatsApp',
            'body' => "Débit de {$this->debitedAmount} XAF pour continuer le service. Nouveau solde: {$this->newWalletBalance} XAF",
            'data' => [
                'type' => 'whatsapp_wallet_debited',
                'debited_amount' => $this->debitedAmount,
                'new_balance' => $this->newWalletBalance,
            ],
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'whatsapp_wallet_debited',
            'title' => 'Wallet débité - Dépassement quota WhatsApp',
            'message' => "Votre wallet a été débité de {$this->debitedAmount} XAF pour continuer le service WhatsApp",
            'debited_amount' => $this->debitedAmount,
            'new_balance' => $this->newWalletBalance,
            'debited_at' => now()->toISOString(),
        ];
    }
}
