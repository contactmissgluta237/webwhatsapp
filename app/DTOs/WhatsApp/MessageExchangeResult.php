<?php

declare(strict_types=1);

namespace App\DTOs\WhatsApp;

use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;

final readonly class MessageExchangeResult
{
    public function __construct(
        public WhatsAppConversation $conversation,
        public WhatsAppMessage $incomingMessage,
        public ?WhatsAppMessage $outgoingMessage = null
    ) {}
}
