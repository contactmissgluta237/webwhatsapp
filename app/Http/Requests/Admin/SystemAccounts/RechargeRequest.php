<?php

namespace App\Http\Requests\Admin\SystemAccounts;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RechargeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'paymentMethod' => ['required', Rule::in(PaymentMethod::values())],
            'amount' => 'required|numeric|min:1',
            'senderName' => 'required|string|max:255',
            'senderAccount' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ];
    }
}
