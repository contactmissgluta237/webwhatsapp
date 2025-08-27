<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\WhatsApp\Conversations;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
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
        // Load conversation messages
        $conversation->load(['messages' => fn ($query) => $query->orderBy('created_at', 'asc')]);

        return view('customer.whatsapp.conversations.show', compact('account', 'conversation'));
    }
}
