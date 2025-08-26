<?php

declare(strict_types=1);

namespace App\Contracts\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Models\WhatsAppAccount;

interface WhatsAppMessageOrchestratorInterface
{
    public function processMessage(
        WhatsAppAccount $account,
        WhatsAppMessageRequestDTO $messageRequest,
        string $conversationHistory, // Format: "user: message\nsystem: réponse\nuser: message..."
    ): WhatsAppMessageResponseDTO;
}
