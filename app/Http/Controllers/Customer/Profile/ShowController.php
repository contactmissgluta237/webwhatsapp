<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ShowController extends Controller
{
    /**
     * Display customer profile page.
     *
     * @endpoint GET /customer/profile
     */
    public function __invoke(Request $request): View
    {
        return view('customer.profile.show');
    }
}
