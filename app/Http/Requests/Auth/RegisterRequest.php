<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'nullable|string|unique:users,phone_number',
            'country_id' => 'sometimes|nullable|exists:countries,id',
            'phone_number_only' => 'nullable|string|min:8|max:15',
            'full_phone_number' => 'sometimes|nullable|string',
            'password' => ['required', 'confirmed', Password::min(6)],
            'terms' => 'accepted',
            'referral_code' => 'nullable|string|exists:users,affiliation_code',
            'locale' => 'required|string|in:en,fr',
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
            'first_name.required' => 'Le prénom est requis.',
            'last_name.required' => 'Le nom de famille est requis.',
            'email.required' => 'L\'email est requis.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'phone_number.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'country_id.required' => 'Veuillez sélectionner un pays.',
            'country_id.exists' => 'Veuillez sélectionner un pays valide.',
            'phone_number_only.required' => 'Le numéro de téléphone est requis.',
            'phone_number_only.min' => 'Le numéro doit contenir au moins 8 chiffres.',
            'phone_number_only.max' => 'Le numéro ne doit pas dépasser 15 chiffres.',
            'password.required' => 'Le mot de passe est requis.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'terms.accepted' => 'Vous devez accepter les conditions d\'utilisation.',
            'referral_code.exists' => 'Ce code de parrainage n\'existe pas.',
            'locale.required' => 'La langue est requise.',
            'locale.in' => 'Veuillez sélectionner une langue valide.',
        ];
    }
}
