<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class ForgotPasswordViewController extends Controller
{
    /**
     * Display the forgot password form.
     *
     * @endpoint GET /forgot-password
     */
    public function __invoke(): View
    {
        return view('auth.forgot-password');
    }
}
