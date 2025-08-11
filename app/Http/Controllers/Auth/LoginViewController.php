<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class LoginViewController extends Controller
{
    /**
     * Handle the incoming request to display the login form.
     */
    public function __invoke(): View
    {
        return view('auth.login');
    }
}
