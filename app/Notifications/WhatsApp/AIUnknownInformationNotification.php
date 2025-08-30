<?php

declare(strict_types=1);

namespace App\Notifications\WhatsApp;

use App\Channels\WhatsAppChannel;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Mail\AIUnknownInformationMail;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppAccountSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

final class AIUnknownInformationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly WhatsAppAccount $account,
        private readonly WhatsAppMessageRequestDTO $incomingMessage,
        private readonly string $aiMessage,
        private $conversationId = null
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        /** @var WhatsAppAccountSetting|null $settings */
        $settings = $this->account->settings;

        Log::info('[AI_UNKNOWN_NOTIFICATION] Determining channels', [
            'account_id' => $this->account->id,
            'enable_email_notifications' => $settings?->enable_email_notifications,
            'notification_email' => $settings?->notification_email,
            'enable_whatsapp_notifications' => $settings?->enable_whatsapp_notifications,
            'notification_whatsapp_number' => $settings?->notification_whatsapp_number,
        ]);

        if ($settings?->enable_email_notifications && $settings?->notification_email) {
            $channels[] = 'mail';
            Log::info('[AI_UNKNOWN_NOTIFICATION] Adding mail channel');
        }

        if ($settings?->enable_whatsapp_notifications && $settings?->notification_whatsapp_number) {
            $channels[] = WhatsAppChannel::class;
            Log::info('[AI_UNKNOWN_NOTIFICATION] Adding WhatsApp channel');
        }

        Log::info('[AI_UNKNOWN_NOTIFICATION] Final channels', ['channels' => $channels]);

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): AIUnknownInformationMail
    {
        $mail = new AIUnknownInformationMail(
            $this->account,
            $this->incomingMessage,
            $this->aiMessage,
            $this->conversationId ?? null
        );

        /** @var WhatsAppAccountSetting|null $settings */
        $settings = $this->account->settings;

        if ($settings?->notification_email) {
            $mail->to($settings->notification_email);
        }

        return $mail;
    }

    /**
     * Get the WhatsApp message representation.
     */
    public function toWhatsApp(object $notifiable): array
    {
        /** @var WhatsAppAccountSetting|null $settings */
        $settings = $this->account->settings;

        $cleanPhoneNumber = $settings?->notification_whatsapp_number;

        Log::info('[AI_UNKNOWN_NOTIFICATION] DEBUG Before cleaning', [
            'raw_phone' => $cleanPhoneNumber,
            'starts_with_plus' => str_starts_with($cleanPhoneNumber ?? '', '+'),
        ]);

        if ($cleanPhoneNumber && str_starts_with($cleanPhoneNumber, '+')) {
            $cleanPhoneNumber = ltrim($cleanPhoneNumber, '+');
        }

        Log::info('[AI_UNKNOWN_NOTIFICATION] DEBUG After cleaning', [
            'clean_phone' => $cleanPhoneNumber,
        ]);

        $data = [
            'to' => $cleanPhoneNumber,
            'session_id' => $this->account->session_id,
            'message' => '🤖 *'.__('AI Information Request')."*\n\n".
                __('Your AI assistant needs help!')." \n\n".
                '📱 *'.__('Customer').":* {$this->incomingMessage->getContactPhone()}\n".
                '❓ *'.__('Question').":* {$this->incomingMessage->body}\n\n".
                __('The AI couldn\'t provide a complete answer. Please review and update your knowledge base.'),
        ];

        Log::info('[AI_UNKNOWN_NOTIFICATION] WhatsApp data prepared', [
            'account_id' => $this->account->id,
            'raw_phone' => $settings?->notification_whatsapp_number,
            'clean_phone' => $cleanPhoneNumber,
            'data' => $data,
        ]);

        return $data;
    }

    /**
     * Get the array representation for database and push notifications.
     */
    public function toArray(object $notifiable): array
    {
        $conversationId = $this->conversationId ?? null;
        $conversationUrl = $conversationId
            ? url('/customer/whatsapp/'.$this->account->id.'/conversations/'.$conversationId)
            : url('/customer/whatsapp/'.$this->account->id);

        return [
            'type' => 'ai_unknown_information',
            'title' => __('AI Information Request'),
            'message' => __('Customer from :phone asked a question your AI couldn\'t answer', [
                'phone' => $this->incomingMessage->getContactPhone(),
            ]),
            'account_id' => $this->account->id,
            'account_name' => $this->account->agent_name ?? $this->account->session_name,
            'customer_phone' => $this->incomingMessage->getContactPhone(),
            'customer_question' => $this->incomingMessage->body,
            'ai_response' => $this->aiMessage,
            'session_id' => $this->account->session_id,
            'conversation_id' => $conversationId,
            'conversation_url' => $conversationUrl,
            'action_text' => __('View Conversation'),
            'action_url' => $conversationUrl,
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
            'title' => __('AI Information Request'),
            'message' => __('Customer from :phone asked a question your AI couldn\'t answer', [
                'phone' => $this->incomingMessage->getContactPhone(),
            ]),
            'data' => $this->toArray($notifiable),
            'timestamp' => now()->toISOString(),
        ];
    }
}
