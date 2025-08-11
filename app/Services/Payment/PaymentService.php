<?php

// app/Services/Payment/PaymentService.php

namespace App\Services\Payment;

use App\Enums\PaymentMethod;
use App\Services\Payment\DTOs\PaymentInitiateDTO;
use App\Services\Payment\DTOs\PaymentResponseDTO;
use App\Services\Payment\Gateways\MyCoolPayGateway;

class PaymentService
{
    public function __construct(
        private MyCoolPayGateway $myCoolPayGateway
    ) {}

    public function initiateRecharge(PaymentInitiateDTO $dto): PaymentResponseDTO
    {
        return $this->getGateway($dto->payment_method)->initiateRecharge($dto);
    }

    public function initiateWithdrawal(PaymentInitiateDTO $dto): PaymentResponseDTO
    {
        return $this->getGateway($dto->payment_method)->initiateWithdrawal($dto);
    }

    public function checkStatus(PaymentMethod $paymentMethod, string $transactionId): PaymentResponseDTO
    {
        return $this->getGateway($paymentMethod)->checkStatus($transactionId);
    }

    public function getBalance(PaymentMethod $paymentMethod): float
    {
        return $this->getGateway($paymentMethod)->getBalance();
    }

    private function getGateway(PaymentMethod $paymentMethod): MyCoolPayGateway
    {
        return match ($paymentMethod) {
            PaymentMethod::MOBILE_MONEY(),
            PaymentMethod::ORANGE_MONEY(),
            PaymentMethod::BANK_CARD() => $this->myCoolPayGateway,
            default => throw new \InvalidArgumentException("Unsupported payment method: {$paymentMethod->value}"),
        };
    }
}
