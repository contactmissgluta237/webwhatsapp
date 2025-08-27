<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Helpers;

use App\Enums\MessageType;
use App\Exceptions\NonTextMessageException;

final class MessageTypeValidator
{
    /**
     * Validate if the message type is supported (text-based)
     *
     * @throws NonTextMessageException
     */
    public static function validateTextMessage(string $messageType): void
    {
        if (! self::isTextMessage($messageType)) {
            throw new NonTextMessageException($messageType);
        }
    }

    /**
     * Check if the message type is text-based
     */
    public static function isTextMessage(string $messageType): bool
    {
        return in_array($messageType, self::getSupportedTextTypes(), true);
    }

    /**
     * Get all supported text message types
     */
    public static function getSupportedTextTypes(): array
    {
        return ['text', 'chat'];
    }

    /**
     * Get all non-text message types
     */
    public static function getNonTextTypes(): array
    {
        return array_diff(
            array_values(MessageType::values()),
            MessageType::getTextTypes()
        );
    }
}
