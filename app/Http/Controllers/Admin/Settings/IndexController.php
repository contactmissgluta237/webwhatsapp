<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::SETTINGS_VIEW()->value);
    }

    /**
     * Display the application settings.
     *
     * Route: GET /admin/settings
     * Name: admin.settings.index
     */
    public function __invoke(Request $request)
    {
        return view('admin.settings.index');
    }
}
