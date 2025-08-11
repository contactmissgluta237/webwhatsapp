<?php

// app/Services/Payment/Contracts/PaymentGatewayInterface.php

namespace App\Services\Payment\Contracts;

use App\Services\Payment\DTOs\PaymentInitiateDTO;
use App\Services\Payment\DTOs\PaymentResponseDTO;

interface PaymentGatewayInterface
{
    public function initiate(PaymentInitiateDTO $dto): PaymentResponseDTO;

    public function checkStatus(string $transactionId): PaymentResponseDTO;

    public function payout(PaymentInitiateDTO $dto): PaymentResponseDTO;

    public function getBalance(): float;
}
