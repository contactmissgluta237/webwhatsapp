<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

use App\DTOs\BaseDTO;

final class UpdateProductDTO extends BaseDTO
{
    public function __construct(
        public string $title,
        public string $description,
        public float $price,
        public bool $is_active = true,
        public array $media = [],
    ) {}
}
