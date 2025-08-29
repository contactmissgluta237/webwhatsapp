<?php

declare(strict_types=1);

namespace App\Livewire\Customer\Packages;

use App\Models\Package;
use App\Services\CouponService;
use App\Services\Customer\PackageSubscriptionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SubscriptionModal extends Component
{
    public Package $package;
    public string $couponCode = '';
    public bool $couponApplied = false;
    /** @var array<string, mixed> */
    public array $couponValidation = [];
    public string $couponMessage = '';
    public string $couponMessageType = '';

    public function mount(Package $package): void
    {
        $this->package = $package;
    }

    public function updatedCouponCode(): void
    {
        if (empty($this->couponCode)) {
            $this->resetCoupon();

            return;
        }

        $this->validateCoupon();
    }

    public function validateCoupon(): void
    {
        if (empty($this->couponCode)) {
            $this->addError('couponCode', __('packages.coupon_code_required'));

            return;
        }

        $user = Auth::user();
        if (! $user) {
            $this->addError('couponCode', 'User not authenticated');

            return;
        }

        $couponService = app(CouponService::class);
        $validation = $couponService->validateCoupon(
            $this->couponCode,
            $user,
            $this->package->getCurrentPrice()
        );

        $this->couponValidation = $validation;

        if ($validation['valid']) {
            $this->couponApplied = true;
            $this->couponMessage = __('packages.coupon_applied_successfully', [
                'savings' => number_format($validation['savings']),
            ]);
            $this->couponMessageType = 'success';
            $this->clearValidation('couponCode');
        } else {
            $this->couponApplied = false;
            $this->couponMessage = $validation['message'];
            $this->couponMessageType = 'danger';
            $this->addError('couponCode', $validation['message']);
        }
    }

    public function removeCoupon(): void
    {
        $this->resetCoupon();
    }

    private function resetCoupon(): void
    {
        $this->couponCode = '';
        $this->couponApplied = false;
        $this->couponValidation = [];
        $this->couponMessage = '';
        $this->couponMessageType = '';
        $this->clearValidation('couponCode');
    }

    public function getOriginalPriceProperty(): float
    {
        return $this->package->getCurrentPrice();
    }

    public function getFinalPriceProperty(): float
    {
        if ($this->couponApplied && ! empty($this->couponValidation)) {
            return $this->couponValidation['final_price'];
        }

        return $this->getOriginalPriceProperty();
    }

    public function getDiscountAmountProperty(): float
    {
        if ($this->couponApplied && ! empty($this->couponValidation)) {
            return $this->couponValidation['discount_amount'];
        }

        return 0;
    }

    public function getDiscountPercentageProperty(): int
    {
        if ($this->couponApplied && ! empty($this->couponValidation['coupon'])) {
            $coupon = $this->couponValidation['coupon'];
            if ($coupon['type'] === 'percentage') {
                return (int) $coupon['value'];
            }
        }

        return 0;
    }

    public function subscribe(): void
    {
        try {
            $subscriptionService = app(PackageSubscriptionService::class);
            $user = Auth::user();

            if (! $user) {
                session()->flash('error', 'User not authenticated');

                return;
            }

            $subscription = $subscriptionService->subscribeDirectly(
                $user,
                $this->package,
                $this->couponCode ?: null
            );

            session()->flash('success', __('packages.subscription_success', [
                'package_name' => $this->package->display_name,
            ]));

            $this->redirect(route('customer.packages.index'));

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.customer.packages.subscription-modal');
    }
}
