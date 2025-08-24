<?php

declare(strict_types=1);

namespace App\Listeners\WhatsApp;

use App\Events\WhatsApp\MessageProcessedEvent;
use App\Repositories\WhatsAppMessageRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Listener responsible for storing both incoming user messages and AI responses to the database
 */
final class StoreMessagesListener
{
    public function __construct(
        private readonly WhatsAppMessageRepositoryInterface $messageRepository
    ) {}

    /**
     * Handle the event and store the complete message exchange
     */
    public function handle(MessageProcessedEvent $event): void
    {
        Log::info('[STORE_MESSAGES] Processing message storage', [
            'session_id' => $event->getSessionId(),
            'from_phone' => $event->getFromPhone(),
            'ai_success' => $event->wasSuccessful(),
        ]);

        try {
            // Store the complete message exchange (incoming + outgoing) in a transaction
            $result = $this->messageRepository->storeMessageExchange(
                $event->account,
                $event->incomingMessage,
                $event->aiResponse
            );

            Log::info('[STORE_MESSAGES] Message exchange stored successfully', [
                'session_id' => $event->getSessionId(),
                'conversation_id' => $result['conversation']->id,
                'incoming_message_id' => $result['incoming_message']->id,
                'outgoing_message_id' => $result['outgoing_message']?->id,
            ]);

        } catch (\Exception $e) {
            Log::error('[STORE_MESSAGES] Failed to store message exchange', [
                'session_id' => $event->getSessionId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't re-throw the exception to avoid breaking the webhook response
        }
    }
}
