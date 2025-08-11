<?php

namespace App\Http\Requests\Auth;

use App\Enums\LoginChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ResetPasswordFormRequest extends FormRequest
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
        return self::getRulesForResetType($this->input('resetType'));
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return self::getMessages();
    }

    public static function getRulesForResetType(?string $resetType): array
    {
        if ($resetType === LoginChannel::EMAIL()->value) {
            return [
                'identifier' => 'required|email|exists:users,email',
                'password' => ['required', 'confirmed', PasswordRule::min(6)],
                'token' => 'required|string',
            ];
        } else {
            return [
                'identifier' => 'required|string|exists:users,phone_number',
                'password' => ['required', 'confirmed', PasswordRule::min(6)],
                'token' => 'required|string',
            ];
        }
    }

    public static function getMessages(): array
    {
        return [
            'identifier.required' => 'L\'identifiant est requis.',
            'identifier.email' => 'L\'email doit être valide.',
            'identifier.exists' => 'Aucun compte n\'est associé à cet identifiant.',
            'identifier.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'password.required' => 'Le mot de passe est requis.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'token.required' => 'Le code de réinitialisation est requis.',
            'token.string' => 'Le code de réinitialisation doit être une chaîne de caractères.',
        ];
    }
}
