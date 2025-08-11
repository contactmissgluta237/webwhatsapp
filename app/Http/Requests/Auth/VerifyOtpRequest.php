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
            'otpCode.required' => 'Le code de vérification est obligatoire.',
            'otpCode.min' => 'Le code doit contenir au moins 4 caractères.',
            'otpCode.max' => 'Le code ne doit pas dépasser 6 caractères.',
        ];
    }
}
