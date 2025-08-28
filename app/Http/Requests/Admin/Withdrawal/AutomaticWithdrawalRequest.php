<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Withdrawal;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;

final class AutomaticWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'string', 'in:'.implode(',', PaymentMethod::values())],
            'receiver_account' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Le client est obligatoire.',
            'customer_id.exists' => 'Le client sélectionné n\'existe pas.',
            'amount.required' => 'Le montant est obligatoire.',
            'amount.min' => 'Le montant doit être supérieur à 0.',
            'payment_method.required' => 'La méthode de paiement est obligatoire.',
            'payment_method.in' => 'La méthode de paiement sélectionnée n\'est pas valide.',
            'receiver_account.required' => 'Le compte destinataire est obligatoire.',
            'receiver_account.max' => 'Le compte destinataire ne peut pas dépasser 255 caractères.',
        ];
    }
}
