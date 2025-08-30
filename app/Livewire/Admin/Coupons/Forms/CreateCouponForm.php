<?php

namespace App\Livewire\Admin\Coupons\Forms;

use App\Enums\CouponStatus;
use App\Http\Requests\Admin\Coupons\CreateCouponRequest;
use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;

class CreateCouponForm extends AbstractCouponForm
{
    public function mount(): void
    {
        $this->code = $this->generateRandomCode();
    }

    public function rules(): array
    {
        $rules = parent::rules();

        // Validation unique pour la création
        $rules['code'] = $rules['code'].'|unique:coupons,code';

        return $rules;
    }

    public function save()
    {
        $this->validate();

        // Validation spéciale pour la valeur selon le type
        if ($this->type === 'percentage' && floatval($this->value) > 100) {
            $this->addError('value', 'Le pourcentage ne peut pas dépasser 100%.');

            return;
        }

        try {
            Coupon::create([
                'code' => $this->code,
                'type' => $this->type,
                'value' => $this->value,
                'status' => CouponStatus::ACTIVE()->value,
                'is_active' => $this->is_active,
                'usage_limit' => $this->usage_limit,
                'per_user_limit' => $this->per_user_limit,
                'used_count' => 0,
                'valid_from' => $this->valid_from ?: null,
                'valid_until' => $this->valid_until ?: null,
                'created_by' => auth()->id(),
            ]);

            session()->flash('success', 'Coupon créé avec succès !');

            return redirect()->route('admin.coupons.index');

        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la création du coupon : '.$e->getMessage());
        }
    }

    protected function customRequest(): FormRequest
    {
        return new CreateCouponRequest;
    }
}
