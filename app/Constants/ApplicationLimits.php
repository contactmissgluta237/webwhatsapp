<?php

declare(strict_types=1);

namespace App\Constants;

final class ApplicationLimits
{
    // Pagination and display limits
    public const DEFAULT_QUERY_LIMIT = 10;
    public const ADMIN_QUERY_LIMIT = 20;
    public const CONVERSATION_MESSAGE_LIMIT = 20;
    public const MAX_MESSAGE_LIMIT = 50;

    // Product limits
    public const MAX_PRODUCTS_PER_MESSAGE = 10;

    // QR Code
    public const QR_CODE_SIZE = 300;

    // Security codes
    public const OTP_CODE_LENGTH = 6;
    public const ACTIVATION_CODE_LENGTH = 6;

    // Context window
    public const CONTEXT_WINDOW_HOURS = 24;

    // AI Tokens
    public const AI_MAX_TOKENS = 1000;
    public const AI_TEST_MAX_TOKENS = 10;
    public const TOKENS_PER_THOUSAND = 1000;

    // Time conversion
    public const SECONDS_TO_MS_MULTIPLIER = 100;
    public const MS_TO_MICROSECONDS = 1000;

    // Default package
    public const DEFAULT_PACKAGE_DURATION_DAYS = 30;
}
