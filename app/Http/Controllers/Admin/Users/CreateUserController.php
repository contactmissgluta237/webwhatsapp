<?php

namespace App\Http\Controllers\Admin\Users;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CreateUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::USERS_CREATE()->value);
    }

    /**
     * Show the form for creating a new user.
     *
     * Route: GET /admin/users/create
     * Name: admin.users.create
     */
    public function __invoke(Request $request)
    {
        // You might want to add authorization here, e.g., $this->authorize('users.create');

        return view('admin.users.create');
    }
}
