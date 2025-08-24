<?php

declare(strict_types=1);

namespace App\Contracts\WhatsApp;

use App\DTOs\AI\AiRequestDTO;
use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;

interface AIProviderServiceInterface
{
    /**
     * Generate AI response using the configured provider
     */
    public function generateResponse(
        AiRequestDTO $aiRequest
    ): ?WhatsAppAIResponseDTO;
}
