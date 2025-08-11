<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone_number' => ['nullable', 'string', 'max:255', Rule::unique('users', 'phone_number')],
            'password' => ['required', 'string', 'min:3'],
            'is_active' => ['required', 'boolean'],
            'selectedRoles' => ['required', 'array', 'min:1'],
            'selectedRoles.*' => ['string', Rule::exists('roles', 'name')],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'selectedRoles.required' => 'Veuillez sélectionner au moins un rôle.',
            'selectedRoles.min' => 'Veuillez sélectionner au moins un rôle.',
            'selectedRoles.*.exists' => 'Le rôle sélectionné n\'est pas valide.',
        ];
    }
}
