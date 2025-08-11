<?php

namespace App\Http\Controllers\Admin\Dashboard;

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
     * Display the admin dashboard.
     *
     * Route: GET /admin/dashboard
     * Name: admin.dashboard
     */
    public function __invoke(Request $request)
    {
        return view('livewire.admin.dashboard');
    }
}
