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
            'email.required' => 'L\'adresse email est requise.',
            'email.email' => 'L\'adresse email doit être valide.',
            'phone_number.required' => 'Le numéro de téléphone complet est requis.',
            'phone_number_only.required' => 'Veuillez saisir votre numéro de téléphone.',
            'phone_number_only.min' => 'Le numéro de téléphone doit contenir au moins 8 chiffres.',
            'phone_number_only.max' => 'Le numéro de téléphone ne doit pas dépasser 15 chiffres.',
            'password.required' => 'Le mot de passe est requis.',
            'country_id.required' => 'Veuillez sélectionner l\'indicateur de votre pays.',
            'country_id.exists' => 'L\'indicateur pays sélectionné n\'existe pas.',
        ];
    }

    protected function prepareForValidation()
    {
        // Dans un contexte Livewire, les propriétés sont déjà disponibles
        // Pas besoin de prepareForValidation car Livewire gère déjà la validation
        // Cette méthode ne doit rien faire dans le contexte Livewire
    }
}
