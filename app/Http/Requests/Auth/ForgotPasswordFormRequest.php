<?php

namespace App\Http\Requests\Auth;

use App\Enums\LoginChannel;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordFormRequest extends FormRequest
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
        return self::getRulesForMethod($this->input('resetMethod'));
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

    public static function getRulesForMethod(string $resetMethod): array
    {
        if ($resetMethod === LoginChannel::EMAIL()->value) {
            return [
                'email' => ['required', 'email', 'exists:users,email'],
            ];
        } else {
            return [
                'country_id' => ['required', 'exists:countries,id'],
                'phone_number_only' => ['required', 'string', 'min:8', 'max:15'],
                'phoneNumber' => ['required', 'string', 'exists:users,phone_number'],
            ];
        }
    }

    public static function getMessages(): array
    {
        return [
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'Veuillez entrer une adresse email valide.',
            'email.exists' => 'Cette adresse email n\'existe pas dans nos enregistrements.',
            'phoneNumber.required' => 'Le numéro de téléphone est obligatoire.',
            'phoneNumber.exists' => 'Ce numéro de téléphone n\'existe pas dans nos enregistrements.',
            'country_id.required' => 'Veuillez sélectionner l\'indicateur de votre pays.',
            'country_id.exists' => 'L\'indicateur pays sélectionné n\'existe pas.',
            'phone_number_only.required' => 'Veuillez saisir votre numéro de téléphone.',
            'phone_number_only.min' => 'Le numéro de téléphone doit contenir au moins 8 chiffres.',
            'phone_number_only.max' => 'Le numéro de téléphone ne doit pas dépasser 15 chiffres.',
        ];
    }
}
