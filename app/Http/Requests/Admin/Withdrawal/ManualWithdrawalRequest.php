<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Withdrawal;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;

final class ManualWithdrawalRequest extends FormRequest
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
            'external_transaction_id' => ['required', 'string', 'max:255', 'unique:external_transactions,external_transaction_id'],
            'description' => ['required', 'string', 'max:500'],
            'sender_name' => ['required', 'string', 'max:255'],
            'sender_account' => ['required', 'string', 'max:255'],
            'receiver_name' => ['required', 'string', 'max:255'],
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
            'external_transaction_id.required' => 'L\'ID de transaction externe est obligatoire.',
            'external_transaction_id.unique' => 'Cet ID de transaction externe existe déjà.',
            'description.required' => 'La description est obligatoire.',
            'description.max' => 'La description ne peut pas dépasser 500 caractères.',
            'sender_name.required' => 'Le nom de l\'expéditeur est obligatoire.',
            'sender_name.max' => 'Le nom de l\'expéditeur ne peut pas dépasser 255 caractères.',
            'sender_account.required' => 'Le compte expéditeur est obligatoire.',
            'sender_account.max' => 'Le compte expéditeur ne peut pas dépasser 255 caractères.',
            'receiver_name.required' => 'Le nom du destinataire est obligatoire.',
            'receiver_name.max' => 'Le nom du destinataire ne peut pas dépasser 255 caractères.',
        ];
    }
}
