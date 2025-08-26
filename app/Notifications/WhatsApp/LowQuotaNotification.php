<?php

declare(strict_types=1);

namespace App\Notifications\WhatsApp;

use App\Channels\PushNotificationChannel;
use App\Mail\WhatsApp\LowQuotaMail;
use App\Models\UserSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LowQuotaNotification extends Notification // Retiré ShouldQueue pour tests
{
    use Queueable;

    public function __construct(
        private readonly UserSubscription $subscription,
        private readonly int $remainingMessages
    ) {}

    /**
     * Get remaining messages for testing
     */
    public function getRemainingMessages(): int
    {
        return $this->remainingMessages;
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
    public function toMail(object $notifiable): LowQuotaMail
    {
        return (new LowQuotaMail($this->subscription, $this->remainingMessages))
            ->to($notifiable->email);
    }

    /**
     * Get the push notification representation.
     */
    public function toPush(object $notifiable): array
    {
        return [
            'title' => 'Quota WhatsApp bientôt épuisé',
            'body' => "Il vous reste {$this->remainingMessages} messages sur {$this->subscription->messages_limit}",
            'data' => [
                'type' => 'whatsapp_low_quota',
                'remaining_messages' => $this->remainingMessages,
                'subscription_id' => $this->subscription->id,
            ],
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'whatsapp_low_quota',
            'title' => 'Quota WhatsApp bientôt épuisé',
            'message' => "Il vous reste {$this->remainingMessages} messages sur {$this->subscription->messages_limit}",
            'remaining_messages' => $this->remainingMessages,
            'total_messages' => $this->subscription->messages_limit,
            'subscription_id' => $this->subscription->id,
            'alert_threshold' => config('whatsapp.billing.alert_threshold_percentage', 20),
        ];
    }
}
