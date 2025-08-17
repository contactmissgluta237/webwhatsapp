<?php

declare(strict_types=1);

namespace App\Contracts\WhatsApp;

use App\DTOs\WhatsApp\ConversationContextDTO;
use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Models\WhatsAppConversation;

interface ContextPreparationServiceInterface
{
    /**
     * Find or create a conversation based on message data
     */
    public function findOrCreateConversation(
        WhatsAppAccountMetadataDTO $accountMetadata,
        WhatsAppMessageRequestDTO $messageRequest
    ): WhatsAppConversation;

    /**
     * Build conversation context with recent messages and metadata
     */
    public function buildConversationContext(
        WhatsAppConversation $conversation,
        ?string $additionalContext = null
    ): ConversationContextDTO;

    /**
     * Store incoming message in the conversation
     */
    public function storeIncomingMessage(
        WhatsAppConversation $conversation,
        WhatsAppMessageRequestDTO $messageRequest
    ): \App\Models\WhatsAppMessage;
}
