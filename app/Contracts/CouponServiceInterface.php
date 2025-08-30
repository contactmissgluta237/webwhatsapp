<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\User;
use App\Models\UserSubscription;

interface CouponServiceInterface
{
    /**
     * Validate a coupon code for a user and price
     *
     * @return array{valid: bool, message: string, final_price?: float, discount_amount?: float, savings?: float, coupon?: ?\App\Models\Coupon}
     *
     * @throws \Exception
     */
    public function validateCoupon(string $couponCode, User $user, float $originalPrice): array;

    /**
     * Apply a coupon to a user subscription
     *
     * @throws \Exception
     */
    public function applyCoupon(Coupon $coupon, User $user, UserSubscription $subscription, float $originalPrice): CouponUsage;
}
