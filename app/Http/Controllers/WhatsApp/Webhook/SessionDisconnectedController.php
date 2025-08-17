<?php

declare(strict_types=1);

namespace App\Http\Controllers\WhatsApp\Webhook;

use App\Events\WhatsAppAccountDisconnectedEvent;
use App\Http\Controllers\Controller;
use App\Models\WhatsAppAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class SessionDisconnectedController extends Controller
{
    /**
     * Handle session disconnected notification from Node.js bridge
     *
     * Route: POST /api/whatsapp/webhook/session-disconnected
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'session_id' => 'required|string',
                'phone_number' => 'nullable|string',
                'disconnected_at' => 'nullable|string|date',
                'reason' => 'nullable|string',
            ]);

            Log::info('Session disconnected webhook received', [
                'session_id' => $validated['session_id'],
                'phone_number' => $validated['phone_number'] ?? null,
                'reason' => $validated['reason'] ?? 'unknown',
            ]);

            $account = WhatsAppAccount::where('session_id', $validated['session_id'])->first();

            if (!$account) {
                Log::warning('Session disconnected: Account not found', [
                    'session_id' => $validated['session_id'],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Account not found',
                ], 404);
            }

            $disconnectedAt = isset($validated['disconnected_at']) 
                ? \Carbon\Carbon::parse($validated['disconnected_at'])
                : now();

            $account->update([
                'status' => 'disconnected',
                'last_disconnected_at' => $disconnectedAt,
            ]);

            Log::info('WhatsApp account marked as disconnected', [
                'account_id' => $account->id,
                'user_id' => $account->user_id,
                'session_name' => $account->session_name,
                'disconnected_at' => $disconnectedAt,
            ]);

            WhatsAppAccountDisconnectedEvent::dispatch($account);

            return response()->json([
                'success' => true,
                'message' => 'Session disconnection processed successfully',
                'account_id' => $account->id,
            ]);

        } catch (ValidationException $e) {
            Log::error('Session disconnected webhook validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to process session disconnected webhook', [
                'session_id' => $request->input('session_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process session disconnection',
            ], 500);
        }
    }
}