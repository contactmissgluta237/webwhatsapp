<?php

declare(strict_types=1);

namespace App\Constants;

final class TimeoutLimits
{
    // Timeouts HTTP
    public const HTTP_CONNECT_TIMEOUT = 10;
    public const HTTP_REQUEST_TIMEOUT = 30;
    public const HTTP_LONG_TIMEOUT = 60;

    // Timeouts WhatsApp
    public const WHATSAPP_QR_TIMEOUT = 12;
    public const WHATSAPP_CONNECT_TIMEOUT = 6;
    public const WHATSAPP_MESSAGE_WAIT_TIME = 30;

    // Retry configuration
    public const DEFAULT_RETRY_ATTEMPTS = 2;
    public const DEFAULT_RETRY_DELAY_MS = 1000;
    public const BACKOFF_MAX_SECONDS = 15;
    public const BACKOFF_MULTIPLIER = 5;

    // Code TTL
    public const OTP_TTL_MINUTES = 10;
    public const ACTIVATION_CODE_TTL_MINUTES = 10;

    // Cache TTL
    public const USER_PRESENCE_TTL_MINUTES = 5;
}
