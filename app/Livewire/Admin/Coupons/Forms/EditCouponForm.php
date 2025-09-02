<?php

namespace App\Livewire\Admin\Coupons\Forms;

use App\Http\Requests\Admin\Coupons\UpdateCouponRequest;
use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;

class EditCouponForm extends AbstractCouponForm
{
    public Coupon $coupon;

    public function mount(Coupon $coupon): void
    {
        $this->coupon = $coupon;

        $this->code = $coupon->code;
        $this->type = $coupon->type;
        $this->value = (string) $coupon->value;
        $this->usage_limit = $coupon->usage_limit;
        $this->per_user_limit = $coupon->per_user_limit;
        $this->is_active = $coupon->is_active;
        $this->valid_from = $coupon->valid_from ? $coupon->valid_from->format('Y-m-d') : '';
        $this->valid_until = $coupon->valid_until ? $coupon->valid_until->format('Y-m-d') : '';
    }

    public function rules(): array
    {
        $rules = parent::rules();

        // Mettre à jour la validation unique pour exclure le coupon actuel
        $rules['code'] = 'required|string|min:3|max:20|regex:/^[A-Z0-9]+$/|unique:coupons,code,'.$this->coupon->id;

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
            $this->coupon->update([
                'code' => $this->code,
                'type' => $this->type,
                'value' => $this->value,
                'status' => $this->is_active ? \App\Enums\CouponStatus::ACTIVE()->value : \App\Enums\CouponStatus::INACTIVE()->value,
                'usage_limit' => $this->usage_limit,
                'per_user_limit' => $this->per_user_limit,
                'is_active' => $this->is_active,
                'valid_from' => $this->valid_from ?: null,
                'valid_until' => $this->valid_until ?: null,
            ]);

            session()->flash('success', 'Coupon modifié avec succès !');

            return redirect()->route('admin.coupons.index');

        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la modification du coupon : '.$e->getMessage());
        }
    }

    protected function customRequest(): FormRequest
    {
        return new UpdateCouponRequest;
    }
}
