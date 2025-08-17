<?php

declare(strict_types=1);

namespace App\Contracts\WhatsApp;

use App\DTOs\AI\AiRequestDTO;
use App\DTOs\WhatsApp\ConversationContextDTO;
use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;

interface MessageBuildServiceInterface
{
    /**
     * Build a complete AI request with system prompt, user message and context
     */
    public function buildAiRequest(
        WhatsAppAccountMetadataDTO $accountMetadata,
        ConversationContextDTO $conversationContext,
        string $userMessage
    ): AiRequestDTO;

    /**
     * Build system prompt with contextual information
     */
    public function buildSystemPrompt(
        WhatsAppAccountMetadataDTO $accountMetadata,
        ConversationContextDTO $conversationContext
    ): string;

    /**
     * Prepare message context for AI processing
     */
    public function prepareMessageContext(
        ConversationContextDTO $conversationContext
    ): array;
}
