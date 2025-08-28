<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class IndexController extends Controller
{
    /**
     * Display the admin dashboard.
     *
     * @endpoint GET /admin/dashboard
     */
    public function __invoke(Request $request): View
    {
        return view('livewire.admin.dashboard');
    }
}
