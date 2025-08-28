<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WhatsApp\Account;

use App\Enums\FlashMessageType;
use App\Http\Controllers\Controller;
use App\Models\WhatsAppAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ToggleAiController extends Controller
{
    /**
     * Toggle AI agent for an account.
     *
     * Route: PATCH /admin/whatsapp/accounts/{account}/toggle-ai
     * Name: admin.whatsapp.accounts.toggle-ai
     */
    public function __invoke(Request $request, WhatsAppAccount $account): RedirectResponse
    {
        $enable = $request->boolean('enable');

        try {
            if ($enable) {
                if (! $account->ai_model_id) {
                    $defaultModel = \App\Models\AiModel::getDefault();
                    if (! $defaultModel) {
                        return redirect()->back()
                            ->with(FlashMessageType::ERROR()->value, 'Aucun modèle IA disponible. Veuillez configurer un agent IA.');
                    }
                    $account->ai_model_id = $defaultModel->id;
                }
                $account->agent_enabled = true;
                $account->save();

                $message = 'Agent IA activé avec succès!';
            } else {
                $account->disableAiAgent();
                $message = 'Agent IA désactivé avec succès!';
            }

            return redirect()->back()->with(FlashMessageType::SUCCESS()->value, $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with(FlashMessageType::ERROR()->value, 'Erreur lors de la modification: '.$e->getMessage());
        }
    }
}
