<?php

declare(strict_types=1);

namespace App\Events\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched after a WhatsApp message has been successfully processed by AI
 * 
 * This event contains both the incoming user message and the AI-generated response,
 * allowing listeners to handle storage, billing, analytics, etc.
 */
final class MessageProcessedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WhatsAppAccount $account,
        public readonly WhatsAppMessageRequestDTO $incomingMessage,
        public readonly WhatsAppMessageResponseDTO $aiResponse
    ) {}

    /**
     * Get the session ID for logging purposes
     */
    public function getSessionId(): string
    {
        return $this->account->session_id;
    }

    /**
     * Get the phone number that sent the message
     */
    public function getFromPhone(): string
    {
        return $this->incomingMessage->from;
    }

    /**
     * Check if the AI response was successful
     */
    public function wasSuccessful(): bool
    {
        return $this->aiResponse->wasSuccessful();
    }
}