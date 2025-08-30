<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Coupons;

use App\Enums\CouponAction;
use App\Enums\CouponStatus;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ToggleStatusController extends Controller
{
    public function __invoke(Request $request, Coupon $coupon): RedirectResponse
    {
        $action = $request->input('action');

        if ($action === CouponAction::ACTIVATE()->value) {
            $coupon->update([
                'status' => CouponStatus::ACTIVE(),
                'is_active' => true,
            ]);

            $message = "Le coupon '{$coupon->code}' a été activé avec succès.";
        } else {
            $coupon->update([
                'status' => CouponStatus::EXPIRED(),
                'is_active' => false,
            ]);

            $message = "Le coupon '{$coupon->code}' a été désactivé avec succès.";
        }

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', $message);
    }
}
