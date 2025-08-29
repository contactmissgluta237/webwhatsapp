<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer\Packages;

use App\Models\Package;
use Illuminate\Foundation\Http\FormRequest;

class SubscribePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'coupon_code.string' => __('packages.coupon_code_invalid_format'),
            'coupon_code.max' => __('packages.coupon_code_too_long'),
        ];
    }

    public function getCouponCode(): ?string
    {
        return $this->input('coupon_code');
    }

    public function getPackage(): Package
    {
        $package = $this->route('package');
        if (! $package instanceof Package) {
            throw new \InvalidArgumentException('Package not found in route parameters');
        }

        return $package;
    }
}
