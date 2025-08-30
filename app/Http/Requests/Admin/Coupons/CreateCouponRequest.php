<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Coupons;

use App\Enums\CouponStatus;
use App\Enums\CouponType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',
                'unique:coupons,code',
            ],
            'type' => [
                'required',
                Rule::in(CouponType::values()),
            ],
            'value' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    if ($this->input('type') === CouponType::PERCENTAGE()->value && $value > 100) {
                        $fail('La valeur du pourcentage ne peut pas dépasser 100.');
                    }
                },
            ],
            'status' => [
                'required',
                Rule::in(CouponStatus::values()),
            ],
            'usage_limit' => [
                'required',
                'integer',
                'min:1',
                'max:10000',
            ],
            'per_user_limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
            'valid_from' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'valid_until' => [
                'nullable',
                'date',
                'after:valid_from',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code coupon est obligatoire.',
            'code.unique' => 'Ce code coupon existe déjà.',
            'code.regex' => 'Le code coupon ne peut contenir que des lettres majuscules, chiffres, tirets et underscores.',
            'type.required' => 'Le type de coupon est obligatoire.',
            'type.in' => 'Le type de coupon sélectionné est invalide.',
            'value.required' => 'La valeur du coupon est obligatoire.',
            'value.numeric' => 'La valeur doit être un nombre.',
            'value.min' => 'La valeur doit être supérieure à 0.',
            'status.required' => 'Le statut est obligatoire.',
            'status.in' => 'Le statut sélectionné est invalide.',
            'usage_limit.required' => 'La limite d\'utilisation est obligatoire.',
            'usage_limit.integer' => 'La limite d\'utilisation doit être un nombre entier.',
            'usage_limit.min' => 'La limite d\'utilisation doit être d\'au moins 1.',
            'usage_limit.max' => 'La limite d\'utilisation ne peut pas dépasser 10000.',
            'per_user_limit.integer' => 'La limite par utilisateur doit être un nombre entier.',
            'per_user_limit.min' => 'La limite par utilisateur doit être d\'au moins 1.',
            'per_user_limit.max' => 'La limite par utilisateur ne peut pas dépasser 100.',
            'valid_from.date' => 'La date de début doit être une date valide.',
            'valid_from.after_or_equal' => 'La date de début ne peut pas être dans le passé.',
            'valid_until.date' => 'La date de fin doit être une date valide.',
            'valid_until.after' => 'La date de fin doit être postérieure à la date de début.',
        ];
    }
}
