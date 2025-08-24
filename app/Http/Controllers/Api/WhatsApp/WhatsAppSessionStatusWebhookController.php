<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WhatsApp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WhatsApp\SessionStatusWebhookRequest;
use App\Models\WhatsAppAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class WhatsAppSessionStatusWebhookController extends Controller
{
    /**
     * Handle the incoming session status webhook from NodeJS.
     *
     * Endpoint: POST /api/whatsapp/webhook/session
     */
    public function __invoke(SessionStatusWebhookRequest $request): JsonResponse
    {
        try {
            WhatsAppAccount::updateStatusFromWebhook(
                $request->input('session_id'),
                $request->input('status'),
                $request->input('phone_number')
            );

            return response()->json(['success' => true, 'message' => 'Session status updated successfully.']);
        } catch (\Exception $e) {
            Log::error('Failed to update WhatsApp session status from webhook', [
                'session_id' => $request->input('session_id'),
                'status' => $request->input('status'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to update session status.'], 500);
        }
    }
}
