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
            'identifier.required' => __('validation.auth.identifier.required'),
            'identifier.email' => __('validation.auth.identifier.email'),
            'identifier.exists' => __('validation.auth.identifier.exists'),
            'identifier.string' => __('validation.auth.identifier.string'),
            'password.required' => __('validation.auth.password.required'),
            'password.confirmed' => __('validation.profile.password.confirmed'),
            'token.required' => __('validation.auth.token.required'),
            'token.string' => __('validation.auth.token.string'),
        ];
    }
}
