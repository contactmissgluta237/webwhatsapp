<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ActivateAccountFormRequest extends FormRequest
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
            'otpCode' => ['required', 'string', 'min:4', 'max:6'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'otpCode.required' => 'Le code d\'activation est obligatoire.',
            'otpCode.min' => 'Le code doit contenir au moins 4 caractères.',
            'otpCode.max' => 'Le code ne doit pas dépasser 6 caractères.',
        ];
    }
}
