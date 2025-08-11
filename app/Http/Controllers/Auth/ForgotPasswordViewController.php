<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class ForgotPasswordViewController extends Controller
{
    /**
     * Handle the incoming request to display the forgot password form.
     */
    public function __invoke(): View
    {
        return view('auth.forgot-password');
    }
}
