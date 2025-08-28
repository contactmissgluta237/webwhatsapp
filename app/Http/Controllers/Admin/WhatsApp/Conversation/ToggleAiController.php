<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WhatsApp\Conversation;

use App\Enums\FlashMessageType;
use App\Http\Controllers\Controller;
use App\Models\WhatsAppConversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ToggleAiController extends Controller
{
    /**
     * Toggle AI for a conversation.
     *
     * Route: PATCH /admin/whatsapp/conversations/{conversation}/toggle-ai
     * Name: admin.whatsapp.conversations.toggle-ai
     */
    public function __invoke(Request $request, WhatsAppConversation $conversation): RedirectResponse
    {
        $enable = $request->boolean('enable');

        try {
            $conversation->update(['is_ai_enabled' => $enable]);

            $message = $enable
                ? 'IA activée pour cette conversation'
                : 'IA désactivée pour cette conversation';

            return redirect()->back()->with(FlashMessageType::SUCCESS()->value, $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with(FlashMessageType::ERROR()->value, 'Erreur lors de la modification: '.$e->getMessage());
        }
    }
}
