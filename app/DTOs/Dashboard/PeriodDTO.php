<?php

declare(strict_types=1);

namespace App\DTOs\Dashboard;

use App\DTOs\BaseDTO;

final class PeriodDTO extends BaseDTO
{
    public function __construct(
        public readonly string $start,
        public readonly string $end
    ) {}
}
