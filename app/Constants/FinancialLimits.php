<?php

declare(strict_types=1);

namespace App\Constants;

final class FinancialLimits
{
    // Recharge amounts
    public const RECHARGE_MIN_AMOUNT = 500;
    public const RECHARGE_MAX_AMOUNT = 50000;

    // Fees and percentages
    public const PERCENTAGE_MULTIPLIER = 100;
    public const FULL_PERCENTAGE = 100.0;

    // Alert thresholds
    public const DEFAULT_ALERT_THRESHOLD_PERCENTAGE = 20;

    // Exchange rates
    public const USD_TO_XAF_DEFAULT_RATE = 650;

    // Default AI costs
    public const DEFAULT_AI_MESSAGE_COST = 15;
}
