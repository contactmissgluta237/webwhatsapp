<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Services\AI\AiUsageTracker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class WhatsAppAccountController extends Controller
{
    public function __construct(
        private readonly AiUsageTracker $aiUsageTracker
    ) {}

    /**
     * Display a listing of all WhatsApp accounts.
     */
    public function index(Request $request): View
    {
        $user = null;

        if ($request->has('user_id') && $request->user_id) {
            $user = User::findOrFail($request->user_id);
        }

        return view('admin.whatsapp.accounts.index', [
            'user' => $user,
            'pageTitle' => $user ? "Comptes WhatsApp - {$user->full_name}" : 'Tous les Comptes WhatsApp',
        ]);
    }

    /**
     * Display the specified WhatsApp account.
     */
    public function show(WhatsAppAccount $account): View
    {
        $account->load([
            'user:id,name,email',
            'conversations' => function (Builder $query): void {
                $query->withCount('messages')
                    ->orderBy('last_message_at', 'desc')
                    ->limit(10);
            },
        ]);

        // Get AI usage statistics
        $recentUsage = $account->aiUsageLogs()
            ->with('conversation:id,contact_name,contact_phone')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.whatsapp.accounts.show', [
            'account' => $account,
            'stats' => [],
            'recentUsage' => $recentUsage,
            'pageTitle' => "Compte WhatsApp - {$account->session_name}",
        ]);
    }

    /**
     * Toggle AI agent for an account.
     */
    public function toggleAi(Request $request, WhatsAppAccount $account)
    {
        $enable = $request->boolean('enable');

        try {
            if ($enable) {
                if (! $account->ai_model_id) {
                    $defaultModel = \App\Models\AiModel::getDefault();
                    if (! $defaultModel) {
                        return redirect()->back()
                            ->with('error', 'Aucun modèle IA disponible. Veuillez configurer un agent IA.');
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

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la modification: '.$e->getMessage());
        }
    }

    /**
     * Delete a WhatsApp account.
     */
    public function destroy(WhatsAppAccount $account)
    {
        try {
            $sessionName = $account->session_name;
            $userName = $account->user->full_name;

            // The cascade delete will handle related records
            $account->delete();

            return redirect()->route('admin.whatsapp.accounts.index')
                ->with('success', "Compte WhatsApp '{$sessionName}' de {$userName} supprimé avec succès.");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression: '.$e->getMessage());
        }
    }
}
