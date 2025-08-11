<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self TEXT()
 * @method static self IMAGE()
 * @method static self DOCUMENT()
 * @method static self AUDIO()
 */
final class MessageType extends Enum
{
    protected static function values(): array
    {
        return [
            'TEXT' => 'text',
            'IMAGE' => 'image',
            'DOCUMENT' => 'document',
            'AUDIO' => 'audio',
        ];
    }

    protected static function labels(): array
    {
        return [
            'TEXT' => 'Texte',
            'IMAGE' => 'Image',
            'DOCUMENT' => 'Document',
            'AUDIO' => 'Audio',
        ];
    }

    public function getIcon(): string
    {
        return match ($this->value) {
            'text' => 'chat-bubble-left',
            'image' => 'photo',
            'document' => 'document',
            'audio' => 'microphone',
            default => 'question-mark-circle',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this->value) {
            'text' => 'bg-gray-100 text-gray-800',
            'image' => 'bg-purple-100 text-purple-800',
            'document' => 'bg-blue-100 text-blue-800',
            'audio' => 'bg-orange-100 text-orange-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
