<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer\Ticket;

use Illuminate\Foundation\Http\FormRequest;

final class CreateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est obligatoire.',
            'title.max' => 'Le titre ne peut pas dépasser 255 caractères.',
            'description.required' => 'La description est obligatoire.',
            'attachments.*.file' => 'Le fichier uploadé n\'est pas valide.',
            'attachments.*.mimes' => 'Les fichiers autorisés sont : jpg, jpeg, png, pdf.',
            'attachments.*.max' => 'La taille maximale d\'un fichier est de 2 Mo.',
        ];
    }
}
