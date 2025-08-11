<?php

namespace App\DTOs\Transaction;

use App\DTOs\BaseDTO;
use App\Enums\PaymentMethod;
use App\Enums\TransactionMode;

class CreateAdminWithdrawalDTO extends BaseDTO
{
    public function __construct(
        public int $customer_id,
        public int $amount,
        public PaymentMethod $payment_method,
        public string $receiver_account,
        public int $created_by,
        public TransactionMode $mode,
        public ?string $external_transaction_id = null,
        public ?string $description = null,
        public ?string $sender_name = null,
        public ?string $sender_account = null,
        public ?string $receiver_name = null,
    ) {}
}
