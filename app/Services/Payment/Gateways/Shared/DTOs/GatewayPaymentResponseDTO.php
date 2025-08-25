<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways\Shared\DTOs;

abstract readonly class GatewayPaymentResponseDTO
{
    abstract public function isSuccess(): bool;

    abstract public function getUserMessageToDisplay(): string;
}
