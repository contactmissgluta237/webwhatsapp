<?php

declare(strict_types=1);

namespace App\DTOs\WhatsApp;

use App\DTOs\BaseDTO;

final class WhatsAppMessageResponseDTO extends BaseDTO
{
    public function __construct(
        public bool $processed,
        public bool $hasAiResponse,
        public ?string $aiResponse = null,
        public ?string $processingError = null,
        public ?WhatsAppAIResponseDTO $aiDetails = null,
        public ?array $metadata = [],
        public int $waitTimeSeconds = 0,
        public int $typingDurationSeconds = 0
    ) {}

    public static function success(string $aiResponse, WhatsAppAIResponseDTO $aiDetails, int $waitTime = 0, int $typingDuration = 0): self
    {
        return new self(
            processed: true,
            hasAiResponse: true,
            aiResponse: $aiResponse,
            processingError: null,
            aiDetails: $aiDetails,
            metadata: [],
            waitTimeSeconds: $waitTime,
            typingDurationSeconds: $typingDuration
        );
    }

    public static function processedWithoutResponse(): self
    {
        return new self(
            processed: true,
            hasAiResponse: false,
            aiResponse: null,
            processingError: null,
            aiDetails: null,
            metadata: []
        );
    }

    public static function error(string $error): self
    {
        return new self(
            processed: false,
            hasAiResponse: false,
            aiResponse: null,
            processingError: $error,
            aiDetails: null,
            metadata: []
        );
    }

    public function wasSuccessful(): bool
    {
        return $this->processed && $this->processingError === null;
    }

    public function hasError(): bool
    {
        return $this->processingError !== null;
    }

    public function toWebhookResponse(): array
    {
        if ($this->hasAiResponse) {
            return [
                'success' => true,
                'response_message' => $this->aiResponse,
                'processed' => true,
                'wait_time_seconds' => $this->waitTimeSeconds,
                'typing_duration_seconds' => $this->typingDurationSeconds,
            ];
        }

        if ($this->hasError()) {
            return [
                'success' => false,
                'error' => $this->processingError,
                'processed' => false,
            ];
        }

        return [
            'success' => true,
            'processed' => true,
            'response_message' => null,
        ];
    }
}
