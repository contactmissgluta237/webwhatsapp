<?php

declare(strict_types=1);

namespace App\Contracts\WhatsApp;

use App\DTOs\AI\AiRequestDTO;
use App\Models\WhatsAppAccount;

interface MessageBuildServiceInterface
{
    /**
     * Build a complete AI request with system prompt, user message and context
     */
    public function buildAiRequest(
        WhatsAppAccount $accountMetadata,
        string $conversationHistory,
        string $userMessage
    ): AiRequestDTO;
}
