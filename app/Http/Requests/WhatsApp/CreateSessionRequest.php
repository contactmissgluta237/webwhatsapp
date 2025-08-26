<?php

declare(strict_types=1);

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

final class CreateSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'session_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('whatsapp_accounts', 'session_name')
                    ->where('user_id', Auth::id()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'session_name.required' => 'Le nom de session est obligatoire.',
            'session_name.string' => 'Le nom de session doit être une chaîne de caractères.',
            'session_name.max' => 'Le nom de session ne peut pas dépasser 255 caractères.',
            'session_name.unique' => 'Ce nom de session existe déjà pour votre compte.',
        ];
    }

    public function getSessionName(): string
    {
        return $this->validated()['session_name'];
    }
}
