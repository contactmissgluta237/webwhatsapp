<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otpCode' => ['required', 'string', 'min:4', 'max:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'otpCode.required' => __('validation.auth.otpCode.required'),
            'otpCode.min' => __('validation.auth.otpCode.min'),
            'otpCode.max' => __('validation.auth.otpCode.max'),
        ];
    }
}
