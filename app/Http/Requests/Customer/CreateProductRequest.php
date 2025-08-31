<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

final class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'price' => 'required|numeric|min:0.01',
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
            'title.required' => __('validation.product.title.required'),
            'title.max' => __('validation.product.title.max'),
            'description.required' => __('validation.product.description.required'),
            'description.max' => __('validation.product.description.max'),
            'price.required' => __('validation.product.price.required'),
            'price.numeric' => __('validation.product.price.numeric'),
            'price.min' => __('validation.product.price.min'),
            'mediaFiles.*.file' => __('validation.product.mediaFiles.file'),
            'mediaFiles.*.mimes' => __('validation.product.mediaFiles.mimes'),
            'mediaFiles.*.max' => __('validation.product.mediaFiles.max'),
        ];
    }
}
