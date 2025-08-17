<?php

declare(strict_types=1);

namespace App\Http\Controllers\WhatsApp\Account;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class DestroyController extends Controller
{
    /**
     * Delete a WhatsApp account.
     * Route: DELETE /whatsapp/{account}
     */
    public function __invoke(Request $request, WhatsAppAccount $account): RedirectResponse
    {
        // Vérification d\'autorisation
        if ($account->user_id !== $request->user()->id) {
            return redirect()->route('whatsapp.index')
                ->with('error', 'Accès non autorisé à ce compte WhatsApp.');
        }

        try {
            $sessionName = $account->session_name;

            Log::info('️ Suppression session WhatsApp', [
                'account_id' => $account->id,
                'session_name' => $sessionName,
                'user_id' => $account->user_id,
            ]);

            // Suppression directe
            $account->delete();

            Log::info('✅ Session WhatsApp supprimée', [
                'session_name' => $sessionName,
            ]);

            return redirect()->route('whatsapp.index')
                ->with('success', "Session « {$sessionName} » supprimée avec succès !");

        } catch (\Exception $e) {
            Log::error('❌ Erreur suppression session WhatsApp', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('whatsapp.index')
                ->with('error', 'Erreur lors de la suppression : '.$e->getMessage());
        }
    }
}
