<?php

namespace App\DTOs\Transaction;

use App\DTOs\BaseDTO;
use App\Enums\PaymentMethod;
use App\Enums\TransactionMode;

class CreateAdminRechargeDTO extends BaseDTO
{
    public function __construct(
        public int $customer_id,
        public int $amount,
        public string $external_transaction_id,
        public string $description,
        public PaymentMethod $payment_method,
        public string $sender_name,
        public string $sender_account,
        public string $receiver_name,
        public string $receiver_account,
        public ?int $created_by,
        public TransactionMode $mode,
    ) {}
}
