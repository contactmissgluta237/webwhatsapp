<?php

namespace App\DTOs\Transaction;

use App\DTOs\BaseDTO;
use App\Enums\PaymentMethod;

class CreateCustomerWithdrawalDTO extends BaseDTO
{
    public function __construct(
        public int $user_id,
        public int $amount,
        public PaymentMethod $payment_method,
        public string $receiver_account,
        public ?int $created_by = null,
    ) {}
}
