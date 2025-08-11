<?php

declare(strict_types=1);

namespace App\Http\Controllers\UserPresence;

use App\Http\Controllers\Controller;
use App\Services\User\UserPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class UserStatusController extends Controller
{
    public function __construct(
        private readonly UserPresenceService $userPresenceService
    ) {}

    /**
     * Get the authenticated user's online status.
     *
     * @endpoint GET /api/user/status
     */
    public function __invoke(): JsonResponse
    {
        $isOnline = $this->userPresenceService->isUserOnline(Auth::id());

        return response()->json([
            'is_online' => $isOnline,
            'user_id' => Auth::id(),
        ]);
    }
}
