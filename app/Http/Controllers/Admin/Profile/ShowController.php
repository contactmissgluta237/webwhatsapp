<?php

namespace App\Http\Controllers\Admin\Profile;

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
     * Display the user's profile.
     *
     * Route: GET /admin/profile
     * Name: admin.profile.show
     */
    public function __invoke(Request $request)
    {
        return view('admin.profile.show');
    }
}
