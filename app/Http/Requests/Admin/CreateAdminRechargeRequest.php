<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAdminRechargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'amount' => [
                'required',
                'integer',
                'min:500',
                'max:50000',
                Rule::in(config('system_settings.predefined_amounts')),
            ],
            'external_transaction_id' => [
                'required',
                'string',
                'max:255',
                'unique:external_transactions,external_transaction_id',
            ],
            'description' => [
                'required',
                'string',
                'max:500',
            ],
            'payment_method' => [
                'required',
                'string',
                Rule::in(array_column(PaymentMethod::cases(), 'value')),
            ],
            'sender_name' => [
                'required',
                'string',
                'max:255',
            ],
            'sender_account' => [
                'required',
                'string',
                'max:255',
            ],
            'receiver_name' => [
                'required',
                'string',
                'max:255',
            ],
            'receiver_account' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Veuillez sélectionner un client.',
            'customer_id.exists' => 'Le client sélectionné n\'existe pas.',
            'amount.required' => 'Veuillez sélectionner un montant.',
            'amount.integer' => 'Le montant doit être un nombre entier.',
            'amount.min' => 'Le montant minimum est de 500 FCFA.',
            'amount.max' => 'Le montant maximum est de 50 000 FCFA.',
            'amount.in' => 'Le montant sélectionné n\'est pas valide.',
            'external_transaction_id.required' => 'L\'ID de transaction externe est requis.',
            'external_transaction_id.unique' => 'Cet ID de transaction existe déjà.',
            'description.required' => 'La description est requise.',
            'description.max' => 'La description ne peut pas dépasser 500 caractères.',
            'payment_method.required' => 'Veuillez sélectionner une méthode de paiement.',
            'payment_method.in' => 'La méthode de paiement sélectionnée n\'est pas valide.',
            'sender_name.required' => 'Le nom de l\'expéditeur est requis.',
            'sender_name.max' => 'Le nom de l\'expéditeur ne peut pas dépasser 255 caractères.',
            'sender_account.required' => 'Le compte de l\'expéditeur est requis.',
            'sender_account.max' => 'Le compte de l\'expéditeur ne peut pas dépasser 255 caractères.',
            'receiver_name.required' => 'Le nom du destinataire est requis.',
            'receiver_name.max' => 'Le nom du destinataire ne peut pas dépasser 255 caractères.',
            'receiver_account.required' => 'Le compte du destinataire est requis.',
            'receiver_account.max' => 'Le compte du destinataire ne peut pas dépasser 255 caractères.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('customer_id')) {
                $user = User::find($this->customer_id);
                if ($user && ! $user->isCustomer()) {
                    $validator->errors()->add('customer_id', 'L\'utilisateur sélectionné n\'est pas un client.');
                }
            }

            $paymentMethod = $this->input('payment_method');
            $senderAccount = $this->input('sender_account');
            $receiverAccount = $this->input('receiver_account');

            if (empty($senderAccount)) {
                $validator->errors()->add('sender_account', 'Le compte de l\'expéditeur est requis.');
            }
            if (empty($receiverAccount)) {
                $validator->errors()->add('receiver_account', 'Le compte du destinataire est requis.');
            }
        });
    }
}
