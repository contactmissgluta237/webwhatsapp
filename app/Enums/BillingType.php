<?php

declare(strict_types=1);

namespace App\Enums;

enum BillingType: string
{
    case SUBSCRIPTION_QUOTA = 'subscription_quota';
    case WALLET_DIRECT = 'wallet_direct';
}
