<?php

namespace App\Http\Controllers\Admin\Users;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::USERS_VIEW()->value);
    }

    /**
     * Display a listing of the users.
     *
     * Route: GET /admin/users
     * Name: admin.users.index
     */
    public function __invoke(Request $request)
    {
        return view('admin.users.index');
    }
}
