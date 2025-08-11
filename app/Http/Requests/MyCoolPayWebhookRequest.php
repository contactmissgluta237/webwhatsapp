<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MyCoolPayWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization will be handled in the controller (signature verification)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['success', 'failed', 'cancelled', 'pending', 'initiated', 'paid', 'error'])],
            'transaction_ref' => ['required', 'string'],
            'app_transaction_ref' => ['required', 'string'],
            'transaction_amount' => ['required', 'numeric', 'min:0'],
            'transaction_currency' => ['required', 'string', Rule::in(['XAF', 'EUR'])],
            'transaction_operator' => ['required', 'string'],
            'operator_transaction_ref' => ['nullable', 'string'],
            'payment_method' => ['required', 'string'],
            'customer_phone_number' => ['required', 'string'],
            'customer_name' => ['nullable', 'string'],
            'customer_email' => ['nullable', 'email'],
            'customer_lang' => ['nullable', 'string'],
            'transaction_reason' => ['nullable', 'string'],
            'transaction_message' => ['nullable', 'string'],
            'hash' => ['required', 'string'],
        ];
    }
}
