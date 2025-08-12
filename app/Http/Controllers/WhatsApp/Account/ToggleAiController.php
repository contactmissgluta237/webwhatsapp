<?php

declare(strict_types=1);

namespace App\Http\Controllers\WhatsApp\Account;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ToggleAiController extends Controller
{
    /**
     * Toggle AI agent status for a WhatsApp account.
     *
     * Route: POST /whatsapp/{account}/toggle-ai
     * Name: whatsapp.toggle-ai
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

        $enable = $request->boolean('enable');

        try {
            if ($enable) {
                // Enable AI agent with default model if none selected
                if (! $account->ai_model_id) {
                    $defaultModel = \App\Models\AiModel::getDefault();
                    if (! $defaultModel) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Aucun modèle IA disponible. Veuillez d\'abord configurer l\'agent IA.',
                        ], 400);
                    }
                    $account->ai_model_id = $defaultModel->id;
                }
                $account->ai_agent_enabled = true;
                $account->save();

                $message = 'Agent IA activé avec succès !';
            } else {
                $account->disableAiAgent();
                $message = 'Agent IA désactivé avec succès !';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification : '.$e->getMessage(),
            ], 500);
        }
    }
}
