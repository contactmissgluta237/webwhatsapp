<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self TEXT()
 * @method static self CHAT()
 * @method static self IMAGE()
 * @method static self VIDEO()
 * @method static self DOCUMENT()
 * @method static self AUDIO()
 * @method static self STICKER()
 * @method static self LOCATION()
 * @method static self CONTACT()
 * @method static self E2E_NOTIFICATION()
 * @method static self NOTIFICATION_TEMPLATE()
 */
final class MessageType extends Enum
{
    public static function values(): array
    {
        return [
            'TEXT' => 'text',
            'CHAT' => 'chat',
            'IMAGE' => 'image',
            'VIDEO' => 'video',
            'DOCUMENT' => 'document',
            'AUDIO' => 'audio',
            'STICKER' => 'sticker',
            'LOCATION' => 'location',
            'CONTACT' => 'contact',
            'E2E_NOTIFICATION' => 'e2e_notification',
            'NOTIFICATION_TEMPLATE' => 'notification_template',
        ];
    }

    protected static function labels(): array
    {
        return [
            'TEXT' => 'Text',
            'CHAT' => 'Chat',
            'IMAGE' => 'Image',
            'VIDEO' => 'Video',
            'DOCUMENT' => 'Document',
            'AUDIO' => 'Audio',
            'STICKER' => 'Sticker',
            'LOCATION' => 'Location',
            'CONTACT' => 'Contact',
            'E2E_NOTIFICATION' => 'E2E Notification',
            'NOTIFICATION_TEMPLATE' => 'Notification Template',
        ];
    }

    public function getIcon(): string
    {
        return match ($this->value) {
            'text', 'chat' => 'chat-bubble-left',
            'image' => 'photo',
            'video' => 'video-camera',
            'document' => 'document',
            'audio' => 'microphone',
            'sticker' => 'face-smile',
            'location' => 'map-pin',
            'contact' => 'user',
            'e2e_notification', 'notification_template' => 'bell',
            default => 'question-mark-circle',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this->value) {
            'text', 'chat' => 'bg-gray-100 text-gray-800',
            'image' => 'bg-purple-100 text-purple-800',
            'video' => 'bg-red-100 text-red-800',
            'document' => 'bg-blue-100 text-blue-800',
            'audio' => 'bg-orange-100 text-orange-800',
            'sticker' => 'bg-yellow-100 text-yellow-800',
            'location' => 'bg-green-100 text-green-800',
            'contact' => 'bg-indigo-100 text-indigo-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function isTextMessage(): bool
    {
        return in_array($this->value, ['text', 'chat'], true);
    }

    public static function getTextTypes(): array
    {
        return ['text', 'chat'];
    }
}
