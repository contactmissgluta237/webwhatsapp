<?php

declare(strict_types=1);

namespace App\Contracts\WhatsApp;

use App\DTOs\AI\AiRequestDTO;
use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;

interface AIProviderServiceInterface
{
    /**
     * Generate AI response using the configured provider
     */
    public function generateResponse(
        WhatsAppAccountMetadataDTO $accountMetadata,
        AiRequestDTO $aiRequest
    ): ?WhatsAppAIResponseDTO;

    /**
     * Validate if AI response generation is possible
     */
    public function canGenerateResponse(
        WhatsAppAccountMetadataDTO $accountMetadata
    ): bool;

    /**
     * Get available AI models for the account
     */
    public function getAvailableModels(
        WhatsAppAccountMetadataDTO $accountMetadata
    ): array;
}
