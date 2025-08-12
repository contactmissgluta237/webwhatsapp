<?php

declare(strict_types=1);

namespace App\Http\Controllers\WhatsApp\Account;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DestroyController extends Controller
{
    /**
     * Delete a WhatsApp account.
     *
     * Route: DELETE /whatsapp/{account}
     * Name: whatsapp.destroy
     */
    public function __invoke(Request $request, WhatsAppAccount $account): JsonResponse
    {
        // Ensure the account belongs to the authenticated user
        if ($account->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé à ce compte WhatsApp.',
            ], 403);
        }

        try {
            $sessionName = $account->session_name;
            $account->delete();

            return response()->json([
                'success' => true,
                'message' => "Session '{$sessionName}' supprimée avec succès !",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : '.$e->getMessage(),
            ], 500);
        }
    }
}
