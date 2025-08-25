<?php

declare(strict_types=1);

namespace App\Services\Payment\DTOs;

use App\Models\ExternalTransaction;

final readonly class PaymentResultDTO
{
    public function __construct(
        public bool $success,
        public ExternalTransaction $transaction,
        public ?string $message,
        public ?object $gatewayResponse = null
    ) {}
}
