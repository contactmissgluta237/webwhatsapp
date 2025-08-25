<?php

declare(strict_types=1);

namespace App\Services\Payment\Contracts;

use App\Models\ExternalTransaction;
use App\Models\Geography\Country;
use App\Services\Payment\DTOs\PaymentIdentifierRequestDTO;
use App\Services\Payment\Gateways\Shared\DTOs\GatewayPaymentResponseDTO;

interface PaymentGatewayInterface
{
    public function initiatePayment(ExternalTransaction $transaction, PaymentIdentifierRequestDTO $request): GatewayPaymentResponseDTO;

    public function verifyTransaction(string $transactionRef): bool;

    public function getSupportedCountries(): array;

    public function isCountrySupported(Country $country): bool;

    public function processWebhook(array $webhookData): ExternalTransaction;
}
