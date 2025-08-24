<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self CONTACT()
 * @method static self GROUP()
 */
final class WhatsAppSuffix extends Enum
{
    protected static function values(): array
    {
        return [
            'CONTACT' => '@c.us',
            'GROUP' => '@g.us',
        ];
    }

    protected static function labels(): array
    {
        return [
            '@c.us' => 'Contact privé',
            '@g.us' => 'Groupe',
        ];
    }

    public function isContact(): bool
    {
        return $this->value === '@c.us';
    }

    public function isGroup(): bool
    {
        return $this->value === '@g.us';
    }

    public function getType(): string
    {
        return match ($this->value) {
            '@c.us' => 'contact',
            '@g.us' => 'group',
        };
    }

    /**
     * Clean a phone number/chat ID from WhatsApp suffixes
     */
    public static function cleanNumber(string $number): string
    {
        return str_replace([self::CONTACT()->value, self::GROUP()->value], '', $number);
    }

    /**
     * Add contact suffix to a clean phone number
     */
    public static function addContactSuffix(string $number): string
    {
        return $number.self::CONTACT()->value;
    }

    /**
     * Detect the type of WhatsApp ID
     */
    public static function detectType(string $whatsappId): ?self
    {
        if (str_contains($whatsappId, self::CONTACT()->value)) {
            return self::CONTACT();
        }

        if (str_contains($whatsappId, self::GROUP()->value)) {
            return self::GROUP();
        }

        return null;
    }
}
