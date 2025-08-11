<?php

declare(strict_types=1);

namespace App\Http\Controllers\PushSubscription;

use App\Http\Controllers\Controller;
use App\Http\Requests\PushSubscription\DestroyPushSubscriptionRequest;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;

final class DestroyPushSubscriptionController extends Controller
{
    /**
     * Delete a push notification subscription.
     *
     * @endpoint DELETE /push/unsubscribe
     */
    public function __invoke(DestroyPushSubscriptionRequest $request): JsonResponse
    {
        $user = $request->user();

        $deleted = PushSubscription::where('subscribable_type', get_class($user))
            ->where('subscribable_id', $user->id)
            ->where('endpoint', $request->validated('endpoint'))
            ->delete();

        return response()->json([
            'message' => $deleted ? 'Subscription deleted successfully.' : 'Subscription not found.',
        ]);
    }
}
