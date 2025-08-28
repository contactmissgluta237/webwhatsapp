<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\SystemAccounts;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RechargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paymentMethod' => ['required', Rule::in(PaymentMethod::values())],
            'amount' => 'required|numeric|min:1',
            'senderName' => 'required|string|max:255',
            'senderAccount' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'paymentMethod.required' => 'La méthode de paiement est obligatoire.',
            'paymentMethod.in' => 'La méthode de paiement sélectionnée n\'est pas valide.',
            'amount.required' => 'Le montant est obligatoire.',
            'amount.numeric' => 'Le montant doit être un nombre.',
            'amount.min' => 'Le montant doit être supérieur à 0.',
            'senderName.required' => 'Le nom de l\'expéditeur est obligatoire.',
            'senderName.max' => 'Le nom de l\'expéditeur ne peut pas dépasser 255 caractères.',
            'senderAccount.required' => 'Le compte expéditeur est obligatoire.',
            'senderAccount.max' => 'Le compte expéditeur ne peut pas dépasser 255 caractères.',
            'description.max' => 'La description ne peut pas dépasser 500 caractères.',
        ];
    }
}
