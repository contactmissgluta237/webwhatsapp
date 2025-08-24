<?php

declare(strict_types=1);

namespace App\DTOs\AI;

use App\DTOs\BaseDTO;
use App\Models\WhatsAppAccount;

class AiRequestDTO extends BaseDTO
{
    public function __construct(
        public string $systemPrompt,
        public string $userMessage,
        public WhatsAppAccount $account,
        public array $config = [],
    ) {}
}
