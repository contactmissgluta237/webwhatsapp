<?php

namespace App\Http\Requests\Auth;

use App\Enums\LoginChannel;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
        return self::getRulesForMethod($this->input('loginMethod'));
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

    public static function getRulesForMethod(string $loginMethod): array
    {
        if ($loginMethod === LoginChannel::EMAIL()->value) {
            return [
                'email' => ['required', 'email'],
                'password' => ['required'],
            ];
        } else {
            return [
                'country_id' => ['required', 'exists:countries,id'],
                'phone_number_only' => ['required', 'string', 'min:8', 'max:15'],
                'phone_number' => ['required', 'string'],
                'password' => ['required'],
            ];
        }
    }

    public static function getMessages(): array
    {
        return [
            'email.required' => __('validation.auth.email.required'),
            'email.email' => __('validation.auth.email.email'),
            'phone_number.required' => __('validation.auth.phone_number.required'),
            'phone_number_only.required' => __('validation.auth.phone_number_only.required'),
            'phone_number_only.min' => __('validation.auth.phone_number_only.min'),
            'phone_number_only.max' => __('validation.auth.phone_number_only.max'),
            'password.required' => __('validation.auth.password.required'),
            'country_id.required' => __('validation.auth.country_id.required'),
            'country_id.exists' => __('validation.auth.country_id.exists'),
        ];
    }
}
