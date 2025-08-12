<?php

declare(strict_types=1);

namespace App\DTOs\AI;

use App\DTOs\BaseDTO;
use Carbon\Carbon;

class AiResponseDTO extends BaseDTO
{
    public function __construct(
        public string $content,
        public array $metadata = [],
        public int $tokensUsed = 0,
        public float $cost = 0.0,
        public Carbon $timestamp = new Carbon,
    ) {}

    public static function create(string $content, array $metadata = []): self
    {
        return new self(
            content: $content,
            metadata: $metadata,
            timestamp: now()
        );
    }
}
