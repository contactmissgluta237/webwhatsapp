<?php

namespace App\Http\Requests\Auth;

use App\Enums\LoginChannel;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $resetMethod = $this->input('resetMethod');

        return [
            'email' => $resetMethod === LoginChannel::EMAIL()->value ? 'required|email|exists:users,email' : 'nullable|email',
            'phoneNumber' => $resetMethod === LoginChannel::PHONE()->value ? 'required|string|min:10|exists:users,phone_number' : 'nullable|string',
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
            'email.required' => 'L\'email est requis.',
            'email.email' => 'L\'email doit être valide.',
            'email.exists' => 'Aucun compte n\'est associé à cet email.',
            'phoneNumber.required' => 'Le numéro de téléphone est requis.',
            'phoneNumber.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'phoneNumber.min' => 'Le numéro de téléphone doit contenir au moins 10 caractères.',
            'phoneNumber.exists' => 'Aucun compte n\'est associé à ce numéro de téléphone.',
        ];
    }
}
