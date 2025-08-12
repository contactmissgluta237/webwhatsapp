<?php

declare(strict_types=1);

namespace App\DTOs\AI;

use App\DTOs\BaseDTO;

class AiRequestDTO extends BaseDTO
{
    public function __construct(
        public string $systemPrompt,
        public string $userMessage,
        public array $config = [],
        public array $context = [],
    ) {}
}
