<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ShowController extends Controller
{
    /**
     * Display the admin profile page.
     *
     * @endpoint GET /admin/profile
     */
    public function __invoke(Request $request): View
    {
        return view('admin.profile.show');
    }
}
