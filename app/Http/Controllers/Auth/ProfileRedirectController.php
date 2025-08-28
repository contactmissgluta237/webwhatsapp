<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class ProfileRedirectController extends Controller
{
    /**
     * Redirect user to appropriate profile page based on role.
     *
     * @endpoint GET /profile
     */
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.profile.show');
        }

        if ($user->isCustomer()) {
            return redirect()->route('customer.profile.show');
        }

        return redirect()->route('login');
    }
}
