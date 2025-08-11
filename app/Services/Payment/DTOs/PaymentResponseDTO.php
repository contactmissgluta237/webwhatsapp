<?php

// app/Services/Payment/DTOs/PaymentResponseDTO.php

namespace App\Services\Payment\DTOs;

use App\DTOs\BaseDTO;
use App\Enums\PaymentStatus;

class PaymentResponseDTO extends BaseDTO
{
    public bool $success;
    public PaymentStatus $status;
    public string $transaction_id;
    public ?string $payment_url = null;
    public ?string $message = null;
    public ?array $gateway_data = null;
    public ?float $amount = null;

    public function isPending(): bool
    {
        return $this->status->equals(PaymentStatus::PENDING());
    }

    public function isCompleted(): bool
    {
        return $this->status->equals(PaymentStatus::COMPLETED());
    }
}
