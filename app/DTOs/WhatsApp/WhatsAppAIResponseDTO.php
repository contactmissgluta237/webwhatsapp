<?php

declare(strict_types=1);

namespace App\DTOs\WhatsApp;

use App\DTOs\BaseDTO;

final class WhatsAppAIResponseDTO extends BaseDTO
{
    public function __construct(
        public string $response,
        public string $model,
        public ?float $confidence = null,
        public ?int $tokensUsed = null,
        public ?float $cost = null,
        public ?array $metadata = []
    ) {}

    public static function fromAiServiceResponse(array $aiResponse): self
    {
        return new self(
            response: $aiResponse['response'],
            model: $aiResponse['model'],
            confidence: $aiResponse['confidence'] ?? null,
            tokensUsed: $aiResponse['tokens_used'] ?? null,
            cost: $aiResponse['cost'] ?? null,
            metadata: $aiResponse['metadata'] ?? []
        );
    }

    public function hasValidResponse(): bool
    {
        return ! empty(trim($this->response));
    }

    public function getResponseLength(): int
    {
        return strlen($this->response);
    }

    public function isHighConfidence(): bool
    {
        return $this->confidence !== null && $this->confidence >= 0.8;
    }
}
