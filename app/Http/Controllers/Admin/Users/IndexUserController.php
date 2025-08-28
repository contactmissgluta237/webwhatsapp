<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class IndexUserController extends Controller
{
    /**
     * Display users listing page.
     *
     * @endpoint GET /admin/users
     */
    public function __invoke(Request $request): View
    {
        return view('admin.users.index');
    }
}
