<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class RegisterViewController extends Controller
{
    /**
     * Handle the incoming request to display the registration form.
     */
    public function __invoke(): View
    {
        return view('auth.register');
    }
}
