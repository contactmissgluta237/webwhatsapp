<?php

declare(strict_types=1);

namespace App\Http\Controllers\WhatsApp\Webhook;

use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsApp\Webhook\SessionConnectedRequest;
use App\Models\WhatsAppAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class SessionConnectedController extends Controller
{
    /**
     * Handle session connected notification from Node.js bridge
     *
     * Route: POST /api/whatsapp/webhook/session-connected
     */
    public function __invoke(SessionConnectedRequest $request): JsonResponse
    {
        $validated = $request->validated();

        Log::info('Session connected webhook received', [
            'session_id' => $validated['session_id'],
            'phone_number' => $validated['phone_number'],
        ]);

        try {
            // Simple validation: check if phone number already has an active session
            $existingAccount = WhatsAppAccount::where('phone_number', $validated['phone_number'])
                ->whereIn('status', ['connected', 'connecting', 'initializing'])
                ->first();

            if ($existingAccount) {
                Log::warning('Phone number already in use', [
                    'phone_number' => $validated['phone_number'],
                    'existing_session' => $existingAccount->session_name,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Ce numéro de téléphone a déjà une session active',
                    'existing_account' => [
                        'session_name' => $existingAccount->session_name,
                        'status' => $existingAccount->status->value,
                        'user_id' => $existingAccount->user_id,
                    ],
                ], 409);
            }

            // Store phone number for temp session (will be used when user saves session)
            cache()->put(
                "whatsapp_temp_session_{$validated['session_id']}",
                [
                    'phone_number' => $validated['phone_number'],
                    'whatsapp_data' => $validated['whatsapp_data'] ?? [],
                    'connected_at' => now(),
                ],
                now()->addMinutes(30) // Expire in 30 minutes
            );

            Log::info('Temp session data stored', [
                'session_id' => $validated['session_id'],
                'phone_number' => $validated['phone_number'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Session connected successfully',
                'phone_validated' => true,
                'ready_to_save' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process session connected webhook', [
                'session_id' => $validated['session_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process session connection',
            ], 500);
        }
    }
}
