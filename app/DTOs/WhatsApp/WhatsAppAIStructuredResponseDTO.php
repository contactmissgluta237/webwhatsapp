<?php

declare(strict_types=1);

namespace App\DTOs\WhatsApp;

use App\DTOs\BaseDTO;
use App\Enums\AIResponseAction;

final class WhatsAppAIStructuredResponseDTO extends BaseDTO
{
    public function __construct(
        public string $message,
        public AIResponseAction $action,
        public array $productIds = [],
        public ?WhatsAppAIResponseDTO $originalResponse = null
    ) {}

    public static function fromAIResponse(WhatsAppAIResponseDTO $aiResponse): self
    {
        $data = json_decode(trim($aiResponse->response), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid AI response: malformed JSON - '.json_last_error_msg());
        }

        if (! isset($data['message'], $data['action'])) {
            throw new \InvalidArgumentException('Invalid AI response: "message" and "action" fields required');
        }

        return new self(
            message: $data['message'],
            action: AIResponseAction::from($data['action']),
            productIds: self::validateProductIds($data['products'] ?? []),
            originalResponse: $aiResponse
        );
    }

    public function isTextOnly(): bool
    {
        return $this->action === AIResponseAction::TEXT();
    }

    public function shouldSendProducts(): bool
    {
        return $this->action->shouldSendProducts() && ! empty($this->productIds);
    }

    public function hasValidProducts(): bool
    {
        return ! empty($this->productIds) &&
               count($this->productIds) <= config('whatsapp.products.max_sent_per_message', 10);
    }

    private static function validateProductIds(array $productIds): array
    {
        $maxProducts = config('whatsapp.products.max_sent_per_message', 10);

        $validIds = array_map('intval', array_filter($productIds, 'is_numeric'));

        return array_slice(array_filter($validIds, fn ($id) => $id > 0), 0, $maxProducts);
    }
}
