<?php

namespace App\Livewire\Admin\Coupons\Forms;

use App\Enums\CouponType;
use App\Services\CouponService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Livewire\Component;

abstract class AbstractCouponForm extends Component
{
    public string $code = '';
    public string $type = 'percentage';
    public string $value = '';
    public int $usage_limit = 100;
    public int $per_user_limit = 1;
    public bool $is_active = true;
    public string $valid_from = '';
    public string $valid_until = '';

    protected CouponService $couponService;

    public function boot(CouponService $couponService): void
    {
        $this->couponService = $couponService;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|min:3|max:20|regex:/^[A-Z0-9]+$/',
            'type' => 'required|in:percentage,fixed_amount',
            'value' => 'required|numeric|min:0.01',
            'usage_limit' => 'required|integer|min:1|max:10000',
            'per_user_limit' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
            'valid_from' => 'nullable|date|after_or_equal:today',
            'valid_until' => 'nullable|date|after:valid_from',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code coupon est obligatoire.',
            'code.regex' => 'Le code ne peut contenir que des lettres majuscules et des chiffres.',
            'type.required' => 'Le type de coupon est obligatoire.',
            'value.required' => 'La valeur du coupon est obligatoire.',
            'value.min' => 'La valeur doit être positive.',
            'usage_limit.required' => 'La limite d\'utilisation est obligatoire.',
            'per_user_limit.required' => 'La limite par utilisateur est obligatoire.',
            'valid_from.after_or_equal' => 'La date de début ne peut pas être dans le passé.',
            'valid_until.after' => 'La date de fin doit être après la date de début.',
        ];
    }

    public function generateRandomCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (\App\Models\Coupon::where('code', $code)->exists());

        return $code;
    }

    public function updatedCode(): void
    {
        $this->code = strtoupper($this->code);
    }

    public function render()
    {
        return view('livewire.admin.coupons.forms.abstract-coupon-form', [
            'couponTypes' => [
                ['value' => CouponType::PERCENTAGE()->value, 'label' => 'Pourcentage'],
                ['value' => CouponType::FIXED_AMOUNT()->value, 'label' => 'Montant Fixe'],
            ],
        ]);
    }

    abstract public function save();

    abstract protected function customRequest(): FormRequest;
}
