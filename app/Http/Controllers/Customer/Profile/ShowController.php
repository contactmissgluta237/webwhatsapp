<?php

namespace App\Http\Controllers\Customer\Profile;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShowController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::PROFILE_VIEW()->value);
    }

    /**
     * Display the customer's profile.
     *
     * Route: GET /customer/profile
     * Name: customer.profile.show
     */
    public function __invoke(Request $request)
    {
        return view('customer.profile.show');
    }
}
