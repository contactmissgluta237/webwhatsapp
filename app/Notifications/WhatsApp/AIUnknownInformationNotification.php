<?php

declare(strict_types=1);

namespace App\Notifications\WhatsApp;

use App\Channels\WhatsAppChannel;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Mail\AIUnknownInformationMail;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppAccountSetting;
use App\Models\WhatsAppConversation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class AIUnknownInformationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public WhatsAppAccount $account,
        public WhatsAppMessageRequestDTO $incomingMessage,
        public string $aiMessage,
        public ?int $conversationId = null
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        /** @var WhatsAppAccountSetting|null $settings */
        $settings = $this->account->settings;

        if ($settings?->enable_email_notifications && $settings?->notification_email) {
            $channels[] = 'mail';
        }

        if ($settings?->enable_whatsapp_notifications && $settings?->notification_whatsapp_number) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): AIUnknownInformationMail
    {
        $conversationId = $this->getValidConversationId();

        $mail = new AIUnknownInformationMail(
            $this->account,
            $this->incomingMessage,
            $this->aiMessage,
            $conversationId
        );

        /** @var WhatsAppAccountSetting|null $settings */
        $settings = $this->account->settings;

        if ($settings?->notification_email) {
            $mail->to($settings->notification_email);
        }

        return $mail;
    }

    public function toWhatsApp(object $notifiable): array
    {
        /** @var WhatsAppAccountSetting|null $settings */
        $settings = $this->account->settings;

        $cleanPhoneNumber = $this->cleanPhoneNumber($settings?->notification_whatsapp_number);
        $userLocale = $this->account->user->locale ?? config('app.locale', 'fr');

        $currentLocale = app()->getLocale();
        app()->setLocale($userLocale);

        $whatsAppData = [
            'to' => $cleanPhoneNumber,
            'session_id' => $this->account->session_id,
            'message' => $this->buildWhatsAppMessage(),
        ];

        app()->setLocale($currentLocale);

        return $whatsAppData;
    }

    public function toArray(object $notifiable): array
    {
        $userLocale = $this->account->user->locale ?? config('app.locale', 'fr');
        $currentLocale = app()->getLocale();
        app()->setLocale($userLocale);

        $conversationId = $this->getValidConversationId();
        $conversationUrl = $conversationId
            ? url('/customer/whatsapp/'.$this->account->id.'/conversations/'.$conversationId)
            : url('/customer/whatsapp/'.$this->account->id);

        $result = [
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

        app()->setLocale($currentLocale);

        return $result;
    }

    private function cleanPhoneNumber(?string $phoneNumber): ?string
    {
        if (! $phoneNumber) {
            return null;
        }

        return str_starts_with($phoneNumber, '+')
            ? ltrim($phoneNumber, '+')
            : $phoneNumber;
    }

    private function buildWhatsAppMessage(): string
    {
        return '*'.__('AI Information Request')."*\n\n".
            __('Your AI assistant needs help!')." \n\n".
            '📱 *'.__('Customer').":* {$this->incomingMessage->getContactPhone()}\n".
            '❓ *'.__('Question').":* {$this->incomingMessage->body}\n\n".
            '🤖 *'.__('AI Response').":* {$this->aiMessage}\n\n".
            __('The AI couldn\'t provide a complete answer. Please review and update your knowledge base.');
    }

    private function getValidConversationId(): ?int
    {
        if ($this->conversationId) {
            return $this->conversationId;
        }

        $conversation = WhatsAppConversation::where('whatsapp_account_id', $this->account->id)
            ->where('contact_phone', $this->incomingMessage->getContactPhone())
            ->first();

        return $conversation?->id;
    }
}
