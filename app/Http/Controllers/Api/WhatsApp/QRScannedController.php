<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WhatsApp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WhatsApp\QRScannedRequest;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class QRScannedController extends Controller
{
    public function __invoke(QRScannedRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            Log::info('QR Code scanned - WhatsApp Bridge notification', [
                'session_id' => $validated['sessionId'],
                'user_id' => $validated['userId'],
                'whatsapp_user' => $validated['whatsappData']['me']['user'],
            ]);

            $user = User::findOrFail($validated['userId']);

            // Créer ou mettre à jour le compte WhatsApp
            $whatsappAccount = WhatsAppAccount::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'session_name' => $validated['sessionId'],
                ],
                [
                    'phone_number' => $validated['whatsappData']['me']['user'],
                    'status' => 'pending_setup',
                    'session_data' => $validated['whatsappData'],
                    'last_seen_at' => now(),
                ]
            );

            Log::info('WhatsApp account created/updated after QR scan', [
                'account_id' => $whatsappAccount->id,
                'session_id' => $validated['sessionId'],
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'QR Code scanné avec succès',
                'account_id' => $whatsappAccount->id,
                'next_step' => 'setup_account_details',
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing QR scan notification', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement du scan QR',
            ], 500);
        }
    }
}
