<?php

namespace App\Http\Controllers\Admin\Users;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class EditUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::USERS_EDIT()->value.',user');
    }

    /**
     * Show the form for editing the specified user.
     *
     * Route: GET /admin/users/{user}/edit
     * Name: admin.users.edit
     */
    public function __invoke(Request $request, User $user)
    {
        return view('admin.users.edit', compact('user'));
    }
}
