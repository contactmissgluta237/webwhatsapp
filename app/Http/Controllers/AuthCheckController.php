<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\RedirectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class AuthCheckController extends Controller
{
    /**
     * Check authentication and redirect to appropriate dashboard.
     *
     * @endpoint GET /
     */
    public function __invoke(Request $request, RedirectionService $redirectionService): RedirectResponse
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            return $redirectionService->redirectToDashboard($user);
        }

        return redirect()->route('login');
    }
}
