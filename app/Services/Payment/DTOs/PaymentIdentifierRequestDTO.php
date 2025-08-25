<?php

declare(strict_types=1);

namespace App\Services\Payment\DTOs;

final readonly class PaymentIdentifierRequestDTO
{
    public function __construct(
        public ?string $phoneNumber = null,
        public ?string $cardNumber = null,
        public ?string $cvv = null,
        public ?int $expiryMonth = null,
        public ?int $expiryYear = null
    ) {}
}
