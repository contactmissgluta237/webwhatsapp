<?php

// app/Services/Payment/DTOs/PaymentInitiateDTO.php

namespace App\Services\Payment\DTOs;

use App\DTOs\BaseDTO;
use App\Enums\PaymentMethod;

class PaymentInitiateDTO extends BaseDTO
{
    public float $amount;
    public PaymentMethod $payment_method;
    public string $reference;
    public string $customer_phone;
    public string $customer_name;
    public string $customer_email;
    public string $currency = 'XAF';
    public ?string $description = null;
    public ?string $customer_lang = 'en';
}
