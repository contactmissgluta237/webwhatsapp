<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CreateUserController extends Controller
{
    /**
     * Display user creation form.
     *
     * @endpoint GET /admin/users/create
     */
    public function __invoke(Request $request): View
    {
        return view('admin.users.create');
    }
}
