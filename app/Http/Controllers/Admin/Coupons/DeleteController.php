<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Coupons;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;

class DeleteController extends Controller
{
    public function __invoke(Coupon $coupon): RedirectResponse
    {
        if ($coupon->used_count > 0) {
            return redirect()
                ->route('admin.coupons.index')
                ->with('error', "Impossible de supprimer le coupon '{$coupon->code}' car il a déjà été utilisé {$coupon->used_count} fois.");
        }

        $couponCode = $coupon->code;
        $coupon->delete();

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', "Le coupon '{$couponCode}' a été supprimé avec succès.");
    }
}
