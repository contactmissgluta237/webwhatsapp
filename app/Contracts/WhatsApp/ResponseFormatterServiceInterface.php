<?php

declare(strict_types=1);

namespace App\Contracts\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Models\WhatsAppConversation;

interface ResponseFormatterServiceInterface
{
    /**
     * Store AI response as outgoing message and format final response
     */
    public function formatAndStoreResponse(
        WhatsAppConversation $conversation,
        WhatsAppAIResponseDTO $aiResponse,
        WhatsAppAccountMetadataDTO $accountMetadata
    ): WhatsAppMessageResponseDTO;

    /**
     * Format response for webhook delivery
     */
    public function formatWebhookResponse(
        WhatsAppMessageResponseDTO $response
    ): array;

    /**
     * Store outgoing AI message in database
     */
    public function storeOutgoingMessage(
        WhatsAppConversation $conversation,
        WhatsAppAIResponseDTO $aiResponse
    ): \App\Models\WhatsAppMessage;
}
