<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\WhatsApp\ProductDataDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Enums\MessageDirection;
use App\Enums\MessageSubtype;
use App\Enums\MessageType;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class EloquentWhatsAppMessageRepository implements WhatsAppMessageRepositoryInterface
{
    public function findOrCreateConversation(
        WhatsAppAccount $account,
        WhatsAppMessageRequestDTO $messageRequest
    ): WhatsAppConversation {
        $chatId = $messageRequest->getChatId();
        $contactPhone = $messageRequest->getContactPhone();

        Log::debug('[MESSAGE_REPO] Finding or creating conversation', [
            'session_id' => $account->session_id,
            'chat_id' => $chatId,
            'contact_phone' => $contactPhone,
            'is_group' => $messageRequest->isFromGroup(),
        ]);

        $conversation = WhatsAppConversation::where('whatsapp_account_id', $account->id)
            ->where('chat_id', $chatId)
            ->first();

        if (! $conversation) {
            $conversation = WhatsAppConversation::create([
                'whatsapp_account_id' => $account->id,
                'chat_id' => $chatId,
                'contact_phone' => $contactPhone,
                'contact_name' => $messageRequest->contactName,
                'public_name' => $messageRequest->getPublicName(),
                'is_group' => $messageRequest->isFromGroup(),
                'is_ai_enabled' => true,
            ]);

            Log::info('[MESSAGE_REPO] New conversation created', [
                'conversation_id' => $conversation->id,
                'chat_id' => $chatId,
                'contact_phone' => $contactPhone,
                'contact_name' => $messageRequest->contactName,
                'public_name' => $messageRequest->getPublicName(),
                'display_name' => $conversation->getDisplayName(),
            ]);
        } else {
            // Update contact information if we have better data
            $updateData = [];

            // Update contact name if we have one and current is null
            if ($messageRequest->contactName && ! $conversation->contact_name) {
                $updateData['contact_name'] = $messageRequest->contactName;
            }

            // Update public name if we have one and current is null
            if ($messageRequest->getPublicName() && ! $conversation->public_name) {
                $updateData['public_name'] = $messageRequest->getPublicName();
            }

            if (! empty($updateData)) {
                $conversation->update($updateData);

                Log::info('[MESSAGE_REPO] Conversation contact info updated', [
                    'conversation_id' => $conversation->id,
                    'updated_fields' => array_keys($updateData),
                    'new_display_name' => $conversation->fresh()->getDisplayName(),
                ]);
            }
        }

        return $conversation;
    }

    public function storeIncomingMessage(
        WhatsAppConversation $conversation,
        WhatsAppMessageRequestDTO $messageRequest
    ): WhatsAppMessage {
        Log::debug('[MESSAGE_REPO] Storing incoming message', [
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
            'is_ai_generated' => false,
        ]);

        Log::info('[MESSAGE_REPO] Incoming message stored', [
            'message_id' => $message->id,
            'whatsapp_message_id' => $messageRequest->id,
            'conversation_id' => $conversation->id,
        ]);

        return $message;
    }

    public function storeOutgoingMessage(
        WhatsAppConversation $conversation,
        WhatsAppAccount $account,
        WhatsAppMessageResponseDTO $aiResponse
    ): WhatsAppMessage {
        Log::debug('[MESSAGE_REPO] Storing outgoing AI message', [
            'conversation_id' => $conversation->id,
            'response_length' => strlen($aiResponse->aiResponse ?? ''),
        ]);

        $message = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'whatsapp_message_id' => null, // AI responses don't have WhatsApp IDs initially
            'direction' => MessageDirection::OUTBOUND(),
            'content' => $aiResponse->aiResponse,
            'message_type' => MessageType::TEXT(),
            'is_ai_generated' => true,
            'ai_model_used' => $account->aiModel?->model_identifier,
            'ai_confidence' => null, // Could be extracted from AI response later
            'processed_at' => now(),
        ]);

        Log::info('[MESSAGE_REPO] Outgoing AI message stored', [
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'ai_model' => $account->aiModel?->model_identifier,
        ]);

        return $message;
    }

    /**
     * Store product messages separately for each product in AI response
     */
    public function storeProductMessages(
        WhatsAppConversation $conversation,
        WhatsAppAccount $account,
        array $products
    ): array {
        if (empty($products)) {
            return [];
        }

        Log::debug('[MESSAGE_REPO] Storing product messages', [
            'conversation_id' => $conversation->id,
            'products_count' => count($products),
        ]);

        $productMessages = [];

        foreach ($products as $product) {
            if (! $product instanceof ProductDataDTO) {
                Log::warning('[MESSAGE_REPO] Invalid product data, skipping', [
                    'product_type' => gettype($product),
                ]);
                continue;
            }

            $productMessage = WhatsAppMessage::create([
                'whatsapp_conversation_id' => $conversation->id,
                'whatsapp_message_id' => null, // Product messages don't have WhatsApp IDs
                'direction' => MessageDirection::OUTBOUND(),
                'content' => $product->formattedProductMessage,
                'message_type' => MessageType::TEXT(),
                'message_subtype' => MessageSubtype::PRODUCT(),
                'media_urls' => $product->mediaUrls,
                'is_ai_generated' => true,
                'ai_model_used' => $account->aiModel?->model_identifier,
                'processed_at' => now(),
            ]);

            $productMessages[] = $productMessage;

            Log::info('[MESSAGE_REPO] Product message stored', [
                'product_message_id' => $productMessage->id,
                'media_count' => count($product->mediaUrls),
                'conversation_id' => $conversation->id,
            ]);
        }

        Log::info('[MESSAGE_REPO] All product messages stored', [
            'conversation_id' => $conversation->id,
            'stored_products' => count($productMessages),
        ]);

        return $productMessages;
    }

    public function storeMessageExchange(
        WhatsAppAccount $account,
        WhatsAppMessageRequestDTO $incomingMessage,
        WhatsAppMessageResponseDTO $aiResponse
    ): array {
        Log::info('[MESSAGE_REPO] Storing complete message exchange', [
            'session_id' => $account->session_id,
            'from_phone' => $incomingMessage->from,
        ]);

        return DB::transaction(function () use ($account, $incomingMessage, $aiResponse) {
            // 1. Find or create conversation
            $conversation = $this->findOrCreateConversation($account, $incomingMessage);

            // 2. Store incoming message
            $incomingMessageRecord = $this->storeIncomingMessage($conversation, $incomingMessage);

            // 3. Store outgoing message if AI response is successful
            $outgoingMessageRecord = null;
            if ($aiResponse->wasSuccessful() && $aiResponse->aiResponse) {
                $outgoingMessageRecord = $this->storeOutgoingMessage($conversation, $account, $aiResponse);
            }

            // 4. Store product messages if any
            $productMessages = [];
            if ($aiResponse->hasProducts()) {
                $productMessages = $this->storeProductMessages($conversation, $account, $aiResponse->products);
            }

            // 5. Update conversation last message timestamp
            $conversation->updateLastMessage(now());

            Log::info('[MESSAGE_REPO] Message exchange stored successfully', [
                'session_id' => $account->session_id,
                'conversation_id' => $conversation->id,
                'incoming_message_id' => $incomingMessageRecord->id,
                'outgoing_message_id' => $outgoingMessageRecord?->id,
                'product_messages_count' => count($productMessages),
            ]);

            return [
                'conversation' => $conversation,
                'incoming_message' => $incomingMessageRecord,
                'outgoing_message' => $outgoingMessageRecord,
                'product_messages' => $productMessages,
            ];
        });
    }

    public function getRecentMessages(
        WhatsAppConversation $conversation,
        int $limit = 20
    ): Collection {
        return $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Determine message type from request data
     */
    private function determineMessageType(WhatsAppMessageRequestDTO $messageRequest): MessageType
    {
        return match ($messageRequest->type) {
            'chat', 'text' => MessageType::TEXT(),
            'image' => MessageType::IMAGE(),
            'audio' => MessageType::AUDIO(),
            'video' => MessageType::AUDIO(), // No VIDEO in enum
            'document' => MessageType::DOCUMENT(),
            'location' => MessageType::TEXT(), // No LOCATION in enum
            default => MessageType::TEXT(),
        };
    }
}
