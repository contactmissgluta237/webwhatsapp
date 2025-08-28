<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Packages;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\RedirectResponse;

class DeleteController extends Controller
{
    public function __invoke(Package $package): RedirectResponse
    {
        // Check if package has active subscriptions
        $activeSubscriptionsCount = $package->subscriptions()
            ->where('status', SubscriptionStatus::ACTIVE()->value)
            ->count();

        if ($activeSubscriptionsCount > 0) {
            return redirect()
                ->route('admin.packages.index')
                ->with('error', "Impossible de supprimer le package '{$package->display_name}' car il a {$activeSubscriptionsCount} souscription(s) active(s).");
        }

        $packageName = $package->display_name;
        $package->delete();

        return redirect()
            ->route('admin.packages.index')
            ->with('success', "Le package '{$packageName}' a été supprimé avec succès.");
    }
}
