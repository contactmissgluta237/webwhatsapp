<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WhatsApp\Account;

use App\Enums\FlashMessageType;
use App\Http\Controllers\Controller;
use App\Models\WhatsAppAccount;
use Illuminate\Http\RedirectResponse;

final class DeleteController extends Controller
{
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

            // The cascade delete will handle related records
            $account->delete();

            return redirect()->route('admin.whatsapp.accounts.index')
                ->with(FlashMessageType::SUCCESS()->value, "Compte WhatsApp '{$sessionName}' de {$userName} supprimé avec succès.");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with(FlashMessageType::ERROR()->value, 'Erreur lors de la suppression: '.$e->getMessage());
        }
    }
}
