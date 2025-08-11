<?php

declare(strict_types=1);

namespace App\Http\Controllers\PushSubscription;

use App\Http\Controllers\Controller;
use App\Http\Requests\PushSubscription\StorePushSubscriptionRequest;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;

final class StorePushSubscriptionController extends Controller
{
    /**
     * Store a new push notification subscription.
     *
     * @endpoint POST /push/subscribe
     */
    public function __invoke(StorePushSubscriptionRequest $request): JsonResponse
    {
        $user = $request->user();

        $subscription = PushSubscription::updateOrCreate(
            [
                'subscribable_type' => get_class($user),
                'subscribable_id' => $user->id,
                'endpoint' => $request->validated('endpoint'),
            ],
            [
                'public_key' => $request->validated('keys.p256dh'),
                'auth_token' => $request->validated('keys.auth'),
                'user_agent' => $request->userAgent(),
                'is_active' => true,
            ]
        );

        return response()->json([
            'message' => 'Push subscription saved successfully.',
            'subscription_id' => $subscription->id,
        ]);
    }
}
