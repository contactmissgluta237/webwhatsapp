<?php

declare(strict_types=1);

namespace App\Helpers;

final class WhatsAppPhoneHelper
{
    /**
     * Transform a clean phone number to WhatsApp identity format
     */
    public static function transformUsablePhoneToWhatsappIdentity(string $phoneNumber): string
    {
        if (str_ends_with($phoneNumber, '@c.us') || str_ends_with($phoneNumber, '@g.us')) {
            return $phoneNumber;
        }

        $cleanNumber = ltrim($phoneNumber, '+');

        return $cleanNumber.'@c.us';
    }

    /**
     * Transform WhatsApp identity format to usable phone number
     */
    public static function transformWhatsappIdentityPhoneToUsable(string $whatsappIdentity): string
    {
        return str_replace(['@c.us', '@g.us'], '', $whatsappIdentity);
    }
}
