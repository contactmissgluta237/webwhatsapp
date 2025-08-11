<?php

namespace App\Services\Payment\MyCoolPay\DTOs;

use App\DTOs\BaseDTO;
use App\Enums\PaymentMethod;
use App\Services\Payment\MyCoolPay\Enums\MyCoolPayCurrency;

class MyCoolPayWebhookDTO extends BaseDTO
{
    public string $status;
    public string $transaction_ref;
    public string $app_transaction_ref;
    public float $transaction_amount;
    public MyCoolPayCurrency $transaction_currency;
    public string $transaction_operator;
    public string $operator_transaction_ref;
    public PaymentMethod $payment_method;
    public string $customer_phone_number;
    public string $customer_name;
    public string $customer_email;
    public string $customer_lang;
    public string $transaction_reason;
    public string $transaction_message;
    public string $hash;
}
