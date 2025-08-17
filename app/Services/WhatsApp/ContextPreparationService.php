<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Contracts\WhatsApp\ContextPreparationServiceInterface;
use App\DTOs\WhatsApp\ConversationContextDTO;
use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Enums\MessageDirection;
use App\Enums\MessageType;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\Log;

final class ContextPreparationService implements ContextPreparationServiceInterface
{
    /**
     * Find or create a conversation based on message data
     */
    public function findOrCreateConversation(
        WhatsAppAccountMetadataDTO $accountMetadata,
        WhatsAppMessageRequestDTO $messageRequest
    ): WhatsAppConversation {
        $account = WhatsAppAccount::findOrFail($accountMetadata->accountId);
        $chatId = $messageRequest->getChatId();
        $contactPhone = $messageRequest->getContactPhone();

        Log::debug('[CONTEXT] Finding or creating conversation', [
            'session_id' => $accountMetadata->sessionId,
            'chat_id' => $chatId,
            'contact_phone' => $contactPhone,
            'is_group' => $messageRequest->isFromGroup(),
        ]);

        $conversation = WhatsAppConversation::where('whatsapp_account_id', $account->id)
            ->where('chat_id', $chatId)
            ->first();

        if (!$conversation) {
            $conversation = $this->createNewConversation(
                $account,
                $messageRequest
            );

            Log::info('[CONTEXT] New conversation created', [
                'conversation_id' => $conversation->id,
                'chat_id' => $chatId,
                'contact_phone' => $contactPhone,
            ]);
        } else {
            Log::debug('[CONTEXT] Existing conversation found', [
                'conversation_id' => $conversation->id,
                'messages_count' => $conversation->messages()->count(),
            ]);
        }

        return $conversation;
    }

    /**
     * Build conversation context with recent messages and metadata
     */
    public function buildConversationContext(
        WhatsAppConversation $conversation,
        ?string $additionalContext = null
    ): ConversationContextDTO {
        Log::debug('[CONTEXT] Building conversation context', [
            'conversation_id' => $conversation->id,
            'has_additional_context' => !empty($additionalContext),
        ]);

        return ConversationContextDTO::fromConversation(
            $conversation,
            $additionalContext
        );
    }

    /**
     * Store incoming message in the conversation
     */
    public function storeIncomingMessage(
        WhatsAppConversation $conversation,
        WhatsAppMessageRequestDTO $messageRequest
    ): WhatsAppMessage {
        Log::debug('[CONTEXT] Storing incoming message', [
            'conversation_id' => $conversation->id,
            'message_id' => $messageRequest->id,
            'message_length' => strlen($messageRequest->body),
        ]);

        $message = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'whatsapp_message_id' => $messageRequest->id,
            'direction' => MessageDirection::INBOUND(),
            'content' => $messageRequest->body,
            'message_type' => $this->determineMessageType($messageRequest),
        ]);

        Log::info('[CONTEXT] Incoming message stored successfully', [
            'message_id' => $message->id,
            'whatsapp_message_id' => $messageRequest->id,
            'conversation_id' => $conversation->id,
        ]);

        return $message;
    }

    /**
     * Get conversation statistics for debugging
     */
    public function getConversationStats(WhatsAppConversation $conversation): array
    {
        return [
            'total_messages' => $conversation->messages()->count(),
            'incoming_messages' => $conversation->messages()
                ->where('direction', MessageDirection::INBOUND())
                ->count(),
            'outgoing_messages' => $conversation->messages()
                ->where('direction', MessageDirection::OUTBOUND())
                ->count(),
            'last_message_at' => $conversation->messages()
                ->latest()
                ->value('created_at'),
            'first_message_at' => $conversation->messages()
                ->oldest()
                ->value('created_at'),
        ];
    }

    /**
     * Create a new conversation record
     */
    private function createNewConversation(
        WhatsAppAccount $account,
        WhatsAppMessageRequestDTO $messageRequest
    ): WhatsAppConversation {
        return WhatsAppConversation::create([
            'whatsapp_account_id' => $account->id,
            'chat_id' => $messageRequest->getChatId(),
            'contact_phone' => $messageRequest->getContactPhone(),
            'contact_name' => $messageRequest->chatName,
            'is_group' => $messageRequest->isFromGroup(),
            'metadata' => [
                'first_message_id' => $messageRequest->id,
                'first_message_timestamp' => $messageRequest->timestamp,
                'message_type' => $messageRequest->type,
            ],
        ]);
    }

    /**
     * Determine message type from request data
     */
    private function determineMessageType(WhatsAppMessageRequestDTO $messageRequest): MessageType
    {
        return match ($messageRequest->type) {
            'chat' => MessageType::TEXT(),
            'image' => MessageType::IMAGE(),
            'audio' => MessageType::AUDIO(),
            'video' => MessageType::AUDIO(), // Pas de VIDEO dans l'enum
            'document' => MessageType::DOCUMENT(),
            'location' => MessageType::TEXT(), // Pas de LOCATION dans l'enum
            default => MessageType::TEXT(),
        };
    }

    /**
     * Build message metadata from request
     */
    private function buildMessageMetadata(WhatsAppMessageRequestDTO $messageRequest): array
    {
        return [
            'whatsapp_timestamp' => $messageRequest->timestamp,
            'message_type' => $messageRequest->type,
            'is_group' => $messageRequest->isFromGroup(),
            'chat_name' => $messageRequest->chatName,
            'raw_metadata' => $messageRequest->metadata,
        ];
    }

    /**
     * Clean old conversation contexts (maintenance method)
     */
    public function cleanOldContexts(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        $deletedCount = WhatsAppMessage::where('created_at', '<', $cutoffDate)
            ->whereHas('conversation', function ($query) {
                $query->whereDoesntHave('messages', function ($subQuery) {
                    $subQuery->where('created_at', '>=', now()->subDays(30));
                });
            })
            ->delete();

        Log::info('[CONTEXT] Cleaned old conversation contexts', [
            'deleted_messages' => $deletedCount,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
        ]);

        return $deletedCount;
    }
}
