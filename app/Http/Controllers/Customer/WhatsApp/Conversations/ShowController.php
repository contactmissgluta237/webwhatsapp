<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\WhatsApp\Conversations;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Display a specific WhatsApp conversation.
 */
final class ShowController extends Controller
{
    /**
     * Display the specified conversation with its messages.
     */
    public function __invoke(Request $request, WhatsAppAccount $account, WhatsAppConversation $conversation): View
    {
        if ($account->user_id !== auth()->id()) {
            abort(403, 'Accès non autorisé à ce compte WhatsApp.');
        }

        if ($conversation->whatsapp_account_id !== $account->id) {
            abort(404, 'Conversation non trouvée pour ce compte.');
        }

        $conversation->load(['messages' => fn (HasMany $query) => $query->orderBy('created_at', 'asc')]);

        return view('customer.whatsapp.conversations.show', compact('account', 'conversation'));
    }
}
