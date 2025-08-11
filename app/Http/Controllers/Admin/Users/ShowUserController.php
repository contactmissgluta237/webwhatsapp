<?php

namespace App\Http\Controllers\Admin\Users;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ShowUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::USERS_VIEW()->value.',user');
    }

    /**
     * Display the specified user.
     *
     * Route: GET /admin/users/{user}
     * Name: admin.users.show
     */
    public function __invoke(Request $request, User $user)
    {
        return view('admin.users.show', compact('user'));
    }
}
