<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WhatsApp\Conversation;

use App\Enums\FlashMessageType;
use App\Http\Controllers\Controller;
use App\Models\WhatsAppConversation;
use Illuminate\Http\RedirectResponse;

final class DeleteController extends Controller
{
    /**
     * Delete a conversation and all its messages.
     *
     * Route: DELETE /admin/whatsapp/conversations/{conversation}
     * Name: admin.whatsapp.conversations.destroy
     */
    public function __invoke(WhatsAppConversation $conversation): RedirectResponse
    {
        try {
            $displayName = $conversation->getDisplayName();
            $accountName = $conversation->whatsappAccount->session_name;

            // The cascade delete will handle messages and usage logs
            $conversation->delete();

            return redirect()->route('admin.whatsapp.conversations.index')
                ->with(FlashMessageType::SUCCESS()->value, "Conversation '{$displayName}' du compte '{$accountName}' supprimée avec succès.");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with(FlashMessageType::ERROR()->value, 'Erreur lors de la suppression: '.$e->getMessage());
        }
    }
}
