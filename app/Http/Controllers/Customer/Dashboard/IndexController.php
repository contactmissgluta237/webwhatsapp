<?php

namespace App\Http\Controllers\Customer\Dashboard;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::DASHBOARD_VIEW()->value);
    }

    /**
     * Display the customer dashboard.
     *
     * Route: GET /customer/dashboard
     * Name: customer.dashboard
     */
    public function __invoke(Request $request)
    {
        return view('livewire.dashboard');
    }
}
