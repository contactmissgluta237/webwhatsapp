<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Notifications\TestNotification;
use App\Services\PushNotificationService;
use App\Services\TestNotificationFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class TestNotificationController extends Controller
{
    public function __construct(
        private readonly PushNotificationService $pushService,
        private readonly TestNotificationFactory $notificationFactory
    ) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            Log::info('🧪 TestNotificationController: Starting notification test', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
            ]);

            $user->notify(new TestNotification($this->pushService, $this->notificationFactory));

            Log::info('✅ TestNotificationController: Notification sent successfully', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent successfully',
                'details' => [
                    'user' => $user->name ?? $user->email,
                    'database_notification' => 'Saved',
                    'push_notifications' => 'Queued for processing',
                    'timestamp' => now()->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('❌ TestNotificationController: Critical error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
