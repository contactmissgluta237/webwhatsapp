<?php

declare(strict_types=1);

namespace App\Http\Controllers\UserPresence;

use App\Http\Controllers\Controller;
use App\Services\User\UserPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class UserStatusController extends Controller
{
    /**
     * Get the authenticated user's online status.
     *
     * @endpoint GET /api/user/status
     */
    public function __invoke(UserPresenceService $userPresenceService): JsonResponse
    {
        $isOnline = $userPresenceService->isUserOnline(Auth::id());

        return response()->json([
            'is_online' => $isOnline,
            'user_id' => Auth::id(),
        ]);
    }
}
