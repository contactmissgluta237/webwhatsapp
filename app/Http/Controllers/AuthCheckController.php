<?php

namespace App\Http\Controllers;

use App\Services\RedirectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthCheckController extends Controller
{
    public function __construct(
        private RedirectionService $redirectionService
    ) {}

    /**
     * Handle the incoming request to check authentication and redirect.
     *
     * Route: GET /
     */
    public function __invoke(Request $request)
    {
        if (Auth::check()) {
            return $this->redirectionService->redirectToDashboard(Auth::user());
        }

        return redirect()->route('login');
    }
}
