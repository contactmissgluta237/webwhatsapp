<?php

declare(strict_types=1);

namespace App\DTOs\WhatsApp;

use App\DTOs\BaseDTO;
use App\Enums\WhatsAppSuffix;

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
        public ?string $contactName = null,
        public ?string $pushName = null,
        public ?string $publicName = null,
        public ?string $displayName = null,
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
            contactName: $messageData['contactName'] ?? null,
            pushName: $messageData['pushName'] ?? null,
            publicName: $messageData['publicName'] ?? null,
            displayName: $messageData['displayName'] ?? null,
            metadata: $messageData['metadata'] ?? []
        );
    }

    public function getChatId(): string
    {
        return $this->from;
    }

    public function getContactPhone(): string
    {
        return WhatsAppSuffix::cleanNumber($this->from);
    }

    public function isFromGroup(): bool
    {
        return $this->isGroup;
    }

    /**
     * Get the best available contact name with fallback priority:
     * 1. Saved contact name (contactName)
     * 2. Public name (publicName/pushName)
     * 3. Display name
     * 4. Phone number
     */
    public function getBestContactName(): string
    {
        return $this->contactName
            ?? $this->publicName
            ?? $this->pushName
            ?? $this->displayName
            ?? $this->getContactPhone();
    }

    /**
     * Get the public name (WhatsApp push name)
     */
    public function getPublicName(): ?string
    {
        return $this->publicName ?? $this->pushName;
    }
}
