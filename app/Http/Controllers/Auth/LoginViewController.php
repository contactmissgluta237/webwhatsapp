<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class LoginViewController extends Controller
{
    /**
     * Display the login form.
     *
     * @endpoint GET /login
     */
    public function __invoke(): View
    {
        return view('auth.login');
    }
}
