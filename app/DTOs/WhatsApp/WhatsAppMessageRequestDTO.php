<?php

declare(strict_types=1);

namespace App\DTOs\WhatsApp;

use App\DTOs\BaseDTO;

final class WhatsAppMessageRequestDTO extends BaseDTO
{
    public function __construct(
        public string $id,
        public string $from,
        public string $body,
        public int $timestamp,
        public string $type,
        public bool $isGroup,
        public ?string $chatName = null,
        public ?array $metadata = []
    ) {}

    public static function fromWebhookData(array $messageData): self
    {
        return new self(
            id: $messageData['id'],
            from: $messageData['from'],
            body: $messageData['body'],
            timestamp: $messageData['timestamp'],
            type: $messageData['type'],
            isGroup: $messageData['isGroup'],
            chatName: $messageData['chatName'] ?? null,
            metadata: $messageData['metadata'] ?? []
        );
    }

    public function getChatId(): string
    {
        return $this->from;
    }

    public function getContactPhone(): string
    {
        return str_replace(['@c.us', '@g.us'], '', $this->from);
    }

    public function isFromGroup(): bool
    {
        return $this->isGroup;
    }
}
