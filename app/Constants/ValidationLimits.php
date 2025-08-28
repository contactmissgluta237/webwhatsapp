<?php

declare(strict_types=1);

namespace App\Constants;

final class ValidationLimits
{
    // Field lengths
    public const GENERAL_TEXT_MAX_LENGTH = 255;
    public const AGENT_NAME_MAX_LENGTH = 100;
    public const TRIGGER_WORDS_MAX_LENGTH = 500;
    public const IGNORE_WORDS_MAX_LENGTH = 500;
    public const DESCRIPTION_MAX_LENGTH = 500;
    public const PRODUCT_DESCRIPTION_MAX_LENGTH = 1000;
    public const CONTEXTUAL_INFO_MAX_LENGTH = 5000;

    // Phone numbers
    public const PHONE_MIN_LENGTH = 8;
    public const PHONE_MAX_LENGTH = 15;

    // Images and files
    public const IMAGE_MAX_SIZE_KB = 2048;
    public const FILE_MAX_SIZE_MB = 10;

    // Text limits for display
    public const DESCRIPTION_PREVIEW_LIMIT = 50;
}
