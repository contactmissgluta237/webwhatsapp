<?php

declare(strict_types=1);

namespace App\DTOs\WhatsApp;

use App\DTOs\BaseDTO;

final class WhatsAppMessageResponseDTO extends BaseDTO
{
    /**
     * @param  ProductDataDTO[]  $products
     */
    public function __construct(
        public bool $processed,
        public bool $hasAiResponse,
        public ?string $aiResponse = null,
        public ?string $processingError = null,
        public ?WhatsAppAIResponseDTO $aiDetails = null,
        public int $waitTimeSeconds = 0,
        public int $typingDurationSeconds = 0,
        public array $products = [],
        public ?string $sessionId = null,
        public ?string $phoneNumber = null
    ) {}

    /**
     * @param  ProductDataDTO[]  $products
     */
    public static function success(
        string $aiResponse,
        WhatsAppAIResponseDTO $aiDetails,
        int $waitTime = 0,
        int $typingDuration = 0,
        array $products = [],
        ?string $sessionId = null,
        ?string $phoneNumber = null
    ): self {
        return new self(
            processed: true,
            hasAiResponse: true,
            aiResponse: $aiResponse,
            processingError: null,
            aiDetails: $aiDetails,
            waitTimeSeconds: $waitTime,
            typingDurationSeconds: $typingDuration,
            products: $products,
            sessionId: $sessionId,
            phoneNumber: $phoneNumber
        );
    }

    public static function processedWithoutResponse(): self
    {
        return new self(
            processed: true,
            hasAiResponse: false
        );
    }

    public static function error(string $error): self
    {
        return new self(
            processed: false,
            hasAiResponse: false,
            processingError: $error
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toWebhookResponse(): array
    {
        if ($this->hasError()) {
            return [
                'success' => false,
                'error' => $this->processingError,
                'processed' => false,
                'session_id' => $this->sessionId,
                'phone_number' => $this->phoneNumber,
            ];
        }

        return [
            'success' => true,
            'processed' => true,
            'session_id' => $this->sessionId,
            'phone_number' => $this->phoneNumber,
            'response_message' => $this->aiResponse,
            'wait_time_seconds' => $this->waitTimeSeconds,
            'typing_duration_seconds' => $this->typingDurationSeconds,
            'products' => array_map(
                fn (ProductDataDTO $product) => $product->toArray(),
                $this->products
            ),
        ];
    }

    public function wasSuccessful(): bool
    {
        return $this->processed && $this->processingError === null;
    }

    public function hasError(): bool
    {
        return $this->processingError !== null;
    }

    public function hasValidWhatsAppData(): bool
    {
        return $this->processed && $this->hasAiResponse && !$this->hasError();
    }

    public function hasProducts(): bool
    {
        return !empty($this->products);
    }

    /**
     * @return int[]
     */
    public function getProductIds(): array
    {
        return array_map(fn(ProductDataDTO $product) => $product->id, $this->products);
    }

    public function getProductsCount(): int
    {
        return count($this->products);
    }
}
