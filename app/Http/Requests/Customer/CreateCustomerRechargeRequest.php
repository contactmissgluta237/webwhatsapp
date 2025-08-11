<?php

namespace App\Http\Requests\Customer;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCustomerRechargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('customer');
    }

    public function rules(): array
    {
        $rules = [
            'amount' => [
                'required',
                'integer',
                'min:500',
                'max:50000',
                Rule::in(config('system_settings.predefined_amounts')),
            ],
            'payment_method' => [
                'required',
                'string',
                Rule::in(array_column(PaymentMethod::cases(), 'value')),
            ],
            'sender_account' => [
                'required',
                'string',
                'max:255',
            ],
        ];

        // Validation spécifique selon le type de paiement
        $paymentMethod = $this->input('payment_method');

        if (in_array($paymentMethod, [PaymentMethod::MOBILE_MONEY()->value, PaymentMethod::ORANGE_MONEY()->value])) {
            // Pour les paiements mobiles, valider le format du numéro de téléphone
            $rules['sender_account'][] = 'regex:/^(\+237)?[0-9]{9}$/';
        } elseif ($paymentMethod === PaymentMethod::BANK_CARD()->value) {
            // Pour les cartes, valider que c'est un numéro masqué
            $rules['sender_account'][] = 'regex:/^\d{4}\*+\d{6}$/';
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            'amount.required' => 'Veuillez sélectionner un montant.',
            'amount.integer' => 'Le montant doit être un nombre entier.',
            'amount.min' => 'Le montant minimum est de 500 FCFA.',
            'amount.max' => 'Le montant maximum est de 50 000 FCFA.',
            'amount.in' => 'Le montant sélectionné n\'est pas valide.',
            'payment_method.required' => 'Veuillez sélectionner une méthode de paiement.',
            'payment_method.in' => 'La méthode de paiement sélectionnée n\'est pas valide.',
            'sender_account.required' => 'Les informations de paiement sont requises.',
            'sender_account.max' => 'Les informations de paiement ne peuvent pas dépasser 255 caractères.',
        ];

        // Messages spécifiques selon le type de paiement
        $paymentMethod = $this->input('payment_method');

        if (in_array($paymentMethod, [PaymentMethod::MOBILE_MONEY()->value, PaymentMethod::ORANGE_MONEY()->value])) {
            $messages['sender_account.regex'] = 'Le format du numéro de téléphone n\'est pas valide (ex: +237670000000).';
        } elseif ($paymentMethod === PaymentMethod::BANK_CARD()->value) {
            $messages['sender_account.regex'] = 'Les informations de carte ne sont pas valides.';
        }

        return $messages;
    }
}
