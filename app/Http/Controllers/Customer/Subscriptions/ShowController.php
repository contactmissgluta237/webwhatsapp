<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\Subscriptions;

use App\Http\Controllers\Controller;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ShowController extends Controller
{
    public function __invoke(Request $request, UserSubscription $subscription): View
    {
        // Ensure user can only view their own subscriptions
        if ($subscription->user_id !== $request->user()->id) {
            abort(403);
        }

        $subscription->load(['package', 'accountUsages.whatsappAccount']);

        return view('customer.subscriptions.show', compact('subscription'));
    }
}
