<?php

namespace App\Http\Controllers\Admin\Referrals;

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
     * Display a listing of the referrals.
     *
     * Route: GET /admin/referrals
     * Name: admin.referrals.index
     */
    public function __invoke(Request $request)
    {
        return view('admin.referrals.index');
    }
}
