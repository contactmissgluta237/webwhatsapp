<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways\MyCoolPay\DTOs;

use App\DTOs\BaseDTO;

final class MyCoolPayWebhookDTO extends BaseDTO
{
    public function __construct(
        public string $application,
        public string $app_transaction_ref,
        public string $operator_transaction_ref,
        public string $transaction_ref,
        public string $transaction_type,
        public int $transaction_amount,
        public int $transaction_fees,
        public string $transaction_currency,
        public string $transaction_operator,
        public string $transaction_status,
        public string $transaction_reason,
        public string $transaction_message,
        public string $customer_phone_number,
        public string $signature
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            application: $data['application'],
            app_transaction_ref: $data['app_transaction_ref'],
            operator_transaction_ref: $data['operator_transaction_ref'],
            transaction_ref: $data['transaction_ref'],
            transaction_type: $data['transaction_type'],
            transaction_amount: (int) $data['transaction_amount'],
            transaction_fees: (int) $data['transaction_fees'],
            transaction_currency: $data['transaction_currency'],
            transaction_operator: $data['transaction_operator'],
            transaction_status: $data['transaction_status'],
            transaction_reason: $data['transaction_reason'],
            transaction_message: $data['transaction_message'],
            customer_phone_number: $data['customer_phone_number'],
            signature: $data['signature']
        );
    }

    public function isSuccess(): bool
    {
        return $this->transaction_status === 'SUCCESS';
    }

    public function isFailed(): bool
    {
        return $this->transaction_status === 'FAILED';
    }

    public function isPending(): bool
    {
        return $this->transaction_status === 'PENDING';
    }
}
