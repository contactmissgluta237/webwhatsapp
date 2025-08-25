<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways\MyCoolPay;

use App\Services\Payment\Gateways\MyCoolPay\Enums\MyCoolPayOperator;
use App\Services\Payment\Gateways\MyCoolPay\Enums\MyCoolPayTransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class MyCoolPayWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller (signature verification)
    }

    public function rules(): array
    {
        return [
            'application' => ['required', 'string'],
            'app_transaction_ref' => ['required', 'string'],
            'operator_transaction_ref' => ['required', 'string'],
            'transaction_ref' => ['required', 'string'],
            'transaction_type' => ['required', 'string', Rule::in([
                MyCoolPayTransactionType::PAYIN()->value,
                MyCoolPayTransactionType::PAYOUT()->value,
            ])],
            'transaction_amount' => ['required', 'numeric', 'min:0'],
            'transaction_fees' => ['required', 'numeric', 'min:0'],
            'transaction_currency' => ['required', 'string', Rule::in(['XAF', 'EUR'])],
            'transaction_operator' => ['required', 'string', Rule::in([
                MyCoolPayOperator::MCP()->value,
                MyCoolPayOperator::CM_MOMO()->value,
                MyCoolPayOperator::CM_OM()->value,
                MyCoolPayOperator::CARD()->value,
            ])],
            'transaction_status' => ['required', 'string', Rule::in([
                'SUCCESS',
                'CANCELED',
                'CANCELLED', // Handle both spellings
                'FAILED',
            ])],
            'transaction_reason' => ['required', 'string'],
            'transaction_message' => ['required', 'string'],
            'customer_phone_number' => ['required', 'string'],
            'signature' => ['required', 'string'],
        ];
    }
}
