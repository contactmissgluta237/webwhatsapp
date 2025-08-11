<?php

namespace App\Http\Controllers\Customer\Referrals;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::REFERRALS_VIEW()->value);
    }

    /**
     * Display a listing of the customer's referrals.
     *
     * Route: GET /customer/referrals
     * Name: customer.referrals.index
     */
    public function __invoke(Request $request)
    {
        return view('customer.referrals.index');
    }
}
