<?php

declare(strict_types=1);

namespace App\DTOs\Dashboard;

use App\DTOs\BaseDTO;
use App\Models\SystemAccount;

final class SystemAccountBalanceDTO extends BaseDTO
{
    public function __construct(
        public readonly string $type,
        public readonly float $balance,
        public readonly string $icon,
        public readonly string $badge
    ) {}

    public static function fromSystemAccount(SystemAccount $account): self
    {
        return new self(
            type: $account->type->label,
            balance: (float) $account->balance,
            icon: $account->type->icon(),
            badge: $account->type->badge()
        );
    }
}
