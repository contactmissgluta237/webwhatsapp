<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WhatsApp\Account;

use App\Enums\FlashMessageType;
use App\Handlers\WhatsApp\WhatsAppSessionSyncHandler;
use App\Http\Controllers\Controller;
use App\Models\WhatsAppAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

final class DeleteController extends Controller
{
    public function __construct(
        private readonly WhatsAppSessionSyncHandler $sessionSyncService
    ) {}

    /**
     * Delete a WhatsApp account.
     *
     * Route: DELETE /admin/whatsapp/accounts/{account}
     * Name: admin.whatsapp.accounts.destroy
     */
    public function __invoke(WhatsAppAccount $account): RedirectResponse
    {
        try {
            $sessionName = $account->session_name;
            $userName = $account->user->full_name;

            Log::info('[ADMIN] Suppression session WhatsApp demandée', [
                'account_id' => $account->id,
                'session_name' => $sessionName,
                'user_name' => $userName,
            ]);

            $this->sessionSyncService->deleteSessionSafely($account);

            return redirect()->route('admin.whatsapp.accounts.index')
                ->with(FlashMessageType::SUCCESS()->value, "Compte WhatsApp '{$sessionName}' de {$userName} supprimé avec succès.");

        } catch (\Exception $e) {
            Log::error('[ADMIN] ❌ Erreur suppression session WhatsApp', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with(FlashMessageType::ERROR()->value, 'Erreur lors de la suppression: '.$e->getMessage());
        }
    }
}
