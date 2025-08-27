<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class BaseDTO extends Data
{
    public function toArrayFiltered(): array
    {
        return array_filter($this->toArray(), function (mixed $value, string $key): bool {
            return $value !== null && $value !== '';
        }, ARRAY_FILTER_USE_BOTH);
    }
}
