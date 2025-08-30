<?php

namespace App\Http\Controllers\Admin\Coupons;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Coupon $coupon): View
    {
        return view('admin.coupons.edit', [
            'title' => 'Modifier le Coupon',
            'coupon' => $coupon,
        ]);
    }
}
