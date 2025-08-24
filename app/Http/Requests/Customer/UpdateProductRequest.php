<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'mediaFiles.*' => [
                'nullable',
                'file',
                'mimes:jpeg,jpg,png,gif,pdf,doc,docx',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est obligatoire.',
            'title.max' => 'Le titre ne peut pas dépasser 255 caractères.',
            'description.required' => 'La description est obligatoire.',
            'description.max' => 'La description ne peut pas dépasser 1000 caractères.',
            'price.required' => 'Le prix est obligatoire.',
            'price.numeric' => 'Le prix doit être un nombre.',
            'price.min' => 'Le prix doit être positif.',
            'mediaFiles.*.file' => 'Le fichier uploadé n\'est pas valide.',
            'mediaFiles.*.mimes' => 'Les fichiers autorisés sont : jpeg, jpg, png, gif, pdf, doc, docx.',
            'mediaFiles.*.max' => 'La taille maximale d\'un fichier est de 10 Mo.',
        ];
    }
}
