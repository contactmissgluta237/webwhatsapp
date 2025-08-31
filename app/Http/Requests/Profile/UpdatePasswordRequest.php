<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
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
            'current_password' => 'required|string',
            'password' => [
                'required',
                'string',
                'min:6',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => __('validation.profile.current_password.required'),
            'current_password.string' => __('validation.profile.current_password.string'),
            'password.required' => __('validation.auth.password.required'),
            'password.string' => __('validation.profile.first_name.string'),
            'password.confirmed' => __('validation.profile.password.confirmed'),
            'password.min' => __('validation.profile.password.min'),
            'password.regex' => __('Le mot de passe doit contenir au moins une lettre minuscule, une lettre majuscule, un chiffre et un caractère spécial (@$!%*?&).'),
        ];
    }
}
