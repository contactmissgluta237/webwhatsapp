<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class RedirectionService
{
    /**
     * Redirect user to appropriate dashboard based on their role
     */
    public function redirectToDashboard(?User $user = null)
    {
        if (! $user) {
            Log::info('RedirectionService: No user provided, redirecting to login');

            return redirect()->route('login');
        }

        $roleRedirects = [
            UserRole::SUPER_ADMIN()->value => 'admin.dashboard',
            UserRole::ADMIN()->value => 'admin.dashboard',
            UserRole::CUSTOMER()->value => 'customer.dashboard',
        ];

        foreach ($roleRedirects as $role => $route) {
            if ($user->hasRole($role)) {
                Log::info("RedirectionService: Redirecting {$role} to {$route}");

                return redirect()->route($route);
            }
        }

        return redirect()->route('dashboard');
    }

    /**
     * Get dashboard route name for a user
     */
    public function getDashboardRoute(?User $user = null): string
    {
        if (! $user) {
            return 'login';
        }

        $roleRoutes = [
            UserRole::SUPER_ADMIN()->value => 'admin.dashboard',
            UserRole::ADMIN()->value => 'admin.dashboard',
            UserRole::CUSTOMER()->value => 'customer.dashboard',
        ];

        foreach ($roleRoutes as $role => $route) {
            if ($user->hasRole($role)) {
                return $route;
            }
        }

        return 'dashboard';
    }

    /**
     * Check if user can access a specific route
     */
    public function canAccessRoute(User $user, string $routeName): bool
    {
        if (str_starts_with($routeName, 'admin.') && ! $user->hasAnyRole([UserRole::ADMIN()->value, UserRole::SUPER_ADMIN()->value])) {
            return false;
        }

        if (str_starts_with($routeName, 'customer.') && ! $user->hasRole(UserRole::CUSTOMER()->value)) {
            return false;
        }

        return true;
    }
}
