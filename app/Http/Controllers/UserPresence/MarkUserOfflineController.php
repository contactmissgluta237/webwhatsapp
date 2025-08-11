<?php

declare(strict_types=1);

namespace App\Http\Controllers\UserPresence;

use App\Http\Controllers\Controller;
use App\Services\User\UserPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class MarkUserOfflineController extends Controller
{
    public function __construct(
        private readonly UserPresenceService $userPresenceService
    ) {}

    /**
     * Mark the authenticated user as offline.
     *
     * @endpoint POST /api/user/offline
     */
    public function __invoke(): JsonResponse
    {
        $this->userPresenceService->markUserOffline(Auth::id());

        return response()->json(['message' => 'User marked as offline.']);
    }
}
