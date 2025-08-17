<?php

declare(strict_types=1);

namespace App\Contracts\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;

interface WhatsAppMessageOrchestratorInterface
{
    /**
     * Orchestrate the complete processing of an incoming WhatsApp message
     */
    public function processIncomingMessage(
        WhatsAppAccountMetadataDTO $accountMetadata,
        WhatsAppMessageRequestDTO $messageRequest
    ): WhatsAppMessageResponseDTO;

    /**
     * Process a simulated message (from ConversationSimulator)
     */
    public function processSimulatedMessage(
        WhatsAppAccountMetadataDTO $accountMetadata,
        string $userMessage,
        ?array $existingContext = null
    ): WhatsAppMessageResponseDTO;

    /**
     * Create account metadata from session information
     */
    public function createAccountMetadata(string $sessionId, string $sessionName): WhatsAppAccountMetadataDTO;
}
