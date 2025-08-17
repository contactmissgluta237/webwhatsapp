<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCreditSystemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message_cost' => [
                'required',
                'numeric',
                'min:0',
                'max:1000'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'message_cost.required' => 'Message cost is required.',
            'message_cost.numeric' => 'Message cost must be a number.',
            'message_cost.min' => 'Message cost must be positive.',
            'message_cost.max' => 'Message cost cannot exceed 1000 FCFA.',
        ];
    }
}