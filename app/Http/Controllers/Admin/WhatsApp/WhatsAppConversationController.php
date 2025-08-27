<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use App\Services\AI\AiUsageTracker;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class WhatsAppConversationController extends Controller
{
    public function __construct(
        private readonly AiUsageTracker $aiUsageTracker
    ) {}

    /**
     * Display a listing of conversations.
     */
    public function index(Request $request): View
    {
        $account = null;
        $user = null;

        if ($request->has('account_id') && $request->account_id) {
            $account = WhatsAppAccount::with('user:id,name')->findOrFail($request->account_id);
        }

        if ($request->has('user_id') && $request->user_id) {
            $user = User::findOrFail($request->user_id);
        }

        $pageTitle = 'Toutes les Conversations WhatsApp';
        if ($account) {
            $pageTitle = "Conversations - {$account->session_name} ({$account->user->full_name})";
        } elseif ($user) {
            $pageTitle = "Conversations WhatsApp - {$user->full_name}";
        }

        return view('admin.whatsapp.conversations.index', [
            'account' => $account,
            'user' => $user,
            'pageTitle' => $pageTitle,
        ]);
    }

    /**
     * Display the specified conversation with messages and AI usage.
     */
    public function show(WhatsAppConversation $conversation): View
    {
        $conversation->load([
            'whatsappAccount:id,session_name,user_id',
            'whatsappAccount.user:id,name,email',
            'messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            },
        ]);

        // Get AI usage logs with detailed information
        $aiUsage = $conversation->aiUsageLogs()
            ->with('message:id,content,created_at,direction')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.whatsapp.conversations.show', [
            'conversation' => $conversation,
            'stats' => [],
            'aiUsage' => $aiUsage,
            'pageTitle' => "Conversation - {$conversation->getDisplayName()}",
        ]);
    }

    /**
     * Toggle AI for a conversation.
     */
    public function toggleAi(Request $request, WhatsAppConversation $conversation)
    {
        $enable = $request->boolean('enable');

        try {
            $conversation->update(['is_ai_enabled' => $enable]);

            $message = $enable
                ? 'IA activée pour cette conversation'
                : 'IA désactivée pour cette conversation';

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la modification: '.$e->getMessage());
        }
    }

    /**
     * Delete a conversation and all its messages.
     */
    public function destroy(WhatsAppConversation $conversation)
    {
        try {
            $displayName = $conversation->getDisplayName();
            $accountName = $conversation->whatsappAccount->session_name;

            // The cascade delete will handle messages and usage logs
            $conversation->delete();

            return redirect()->route('admin.whatsapp.conversations.index')
                ->with('success', "Conversation '{$displayName}' du compte '{$accountName}' supprimée avec succès.");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression: '.$e->getMessage());
        }
    }
}
