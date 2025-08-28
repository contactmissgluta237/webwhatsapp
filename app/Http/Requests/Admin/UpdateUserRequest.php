<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    private ?User $user = null;

    public function authorize(): bool
    {
        return true;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function rules(): array
    {
        $user = $this->user ?? $this->route('user');

        if (! $user) {
            return [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'phone_number' => ['nullable', 'string', 'max:255', 'unique:users,phone_number'],
                'password' => ['nullable', 'string', 'min:3'],
                'is_active' => ['required', 'boolean'],
                'selectedRoles' => ['required', 'array', 'min:1'],
                'selectedRoles.*' => ['string', Rule::exists('roles', 'name')],
                'image' => ['nullable', 'image', 'max:2048'],
            ];
        }

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone_number' => ['nullable', 'string', 'max:255', Rule::unique('users', 'phone_number')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:3'],
            'is_active' => ['required', 'boolean'],
            'selectedRoles' => ['required', 'array', 'min:1'],
            'selectedRoles.*' => ['string', Rule::exists('roles', 'name')],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Le prénom est obligatoire.',
            'first_name.max' => 'Le prénom ne peut pas dépasser 255 caractères.',
            'last_name.required' => 'Le nom est obligatoire.',
            'last_name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'phone_number.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'password.min' => 'Le mot de passe doit contenir au moins 3 caractères.',
            'is_active.required' => 'Le statut est obligatoire.',
            'selectedRoles.required' => 'Veuillez sélectionner au moins un rôle.',
            'selectedRoles.min' => 'Veuillez sélectionner au moins un rôle.',
            'selectedRoles.*.exists' => 'Le rôle sélectionné n\'est pas valide.',
            'image.image' => 'Le fichier doit être une image.',
            'image.max' => 'L\'image ne peut pas dépasser 2 Mo.',
        ];
    }
}
