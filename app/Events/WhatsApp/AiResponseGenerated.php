<?php

declare(strict_types=1);

namespace App\Events\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiResponseGenerated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WhatsAppAccount $account,
        public readonly WhatsAppMessageRequestDTO $messageRequest,
        public readonly WhatsAppAIResponseDTO $aiResponse,
        public readonly string $requestBody,
        public readonly float $processingTimeMs
    ) {}
}
