<?php

declare(strict_types=1);

namespace App\Notifications\WhatsApp;

use App\Channels\WhatsAppNotificationChannel;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppAccountSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AIUnknownInformationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private WhatsAppAccount $account,
        private WhatsAppMessageRequestDTO $incomingMessage,
        private string $aiMessage
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        /** @var WhatsAppAccountSetting|null $settings */
        $settings = $this->account->settings;

        if ($settings?->enable_email_notifications) {
            $channels[] = 'mail';
        }

        if ($settings?->enable_whatsapp_notifications) {
            $channels[] = WhatsAppNotificationChannel::class;
        }

        // Add push notifications if enabled
        $channels[] = 'broadcast';

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('AI Information Request - '.($this->account->agent_name ?? $this->account->session_name))
            ->greeting('Hello!')
            ->line('Your AI assistant received a question it couldn\'t answer from a customer.')
            ->line('**Customer Phone:** '.$this->incomingMessage->from)
            ->line('**Customer Question:** '.$this->incomingMessage->body)
            ->line('**AI Response:** '.$this->aiMessage)
            ->action('View WhatsApp Account', url('/customer/whatsapp/'.$this->account->id))
            ->line('Please review and provide the information to improve future responses.');
    }

    /**
     * Get the WhatsApp message representation.
     */
    public function toWhatsApp(object $notifiable): array
    {
        /** @var WhatsAppAccountSetting|null $settings */
        $settings = $this->account->settings;

        return [
            'to' => $settings?->notification_whatsapp_number,
            'message' => "🤖 *AI Information Request*\n\n".
                "Your AI assistant needs help!\n\n".
                "📱 *Customer:* {$this->incomingMessage->from}\n".
                "❓ *Question:* {$this->incomingMessage->body}\n\n".
                "The AI couldn't provide a complete answer. Please review and update your knowledge base.",
        ];
    }

    /**
     * Get the array representation for database and push notifications.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ai_unknown_information',
            'account_id' => $this->account->id,
            'account_name' => $this->account->agent_name ?? $this->account->session_name,
            'customer_phone' => $this->incomingMessage->from,
            'customer_question' => $this->incomingMessage->body,
            'ai_response' => $this->aiMessage,
            'session_id' => $this->account->session_id,
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * Get the broadcastable representation for push notifications.
     */
    public function toBroadcast(object $notifiable): array
    {
        return [
            'id' => $this->id,
            'type' => 'ai_unknown_information',
            'title' => 'AI Information Request',
            'message' => "Customer from {$this->incomingMessage->from} asked a question your AI couldn't answer",
            'data' => $this->toArray($notifiable),
            'timestamp' => now()->toISOString(),
        ];
    }
}
