<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ResetPasswordViewController extends Controller
{
    /**
     * Handle the incoming request to display the password reset form.
     */
    public function __invoke(Request $request): View
    {
        $identifier = $request->route('identifier') ?? $request->email;
        $resetType = $request->route('resetType') ?? 'email';

        return view('auth.reset-password', [
            'token' => $request->route('token'),
            'identifier' => $identifier,
            'resetType' => $resetType,
        ]);
    }
}
