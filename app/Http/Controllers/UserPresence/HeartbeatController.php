<?php

declare(strict_types=1);

namespace App\Http\Controllers\UserPresence;

use App\Http\Controllers\Controller;
use App\Services\User\UserPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class HeartbeatController extends Controller
{
    public function __construct(
        private readonly UserPresenceService $userPresenceService
    ) {}

    /**
     * Mark the authenticated user as online (heartbeat).
     *
     * @endpoint POST /api/user/heartbeat
     */
    public function __invoke(): JsonResponse
    {
        $this->userPresenceService->markUserOnline(Auth::id());

        return response()->json([
            'message' => 'Heartbeat recorded.',
            'timestamp' => now()->toISOString(),
        ]);
    }
}
