<?php

declare(strict_types=1);

namespace App\Mail;

use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Models\WhatsAppAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AIUnknownInformationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public WhatsAppAccount $account,
        public WhatsAppMessageRequestDTO $incomingMessage,
        public string $aiMessage,
        public ?int $conversationId = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('AI Information Request').' - '.($this->account->agent_name ?? $this->account->session_name),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ai-unknown-information',
            with: [
                'account' => $this->account,
                'incomingMessage' => $this->incomingMessage,
                'aiMessage' => $this->aiMessage,
                'conversationId' => $this->conversationId,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
