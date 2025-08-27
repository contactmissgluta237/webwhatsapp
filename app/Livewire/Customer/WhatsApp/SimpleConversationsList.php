<?php

declare(strict_types=1);

namespace App\Livewire\Customer\WhatsApp;

use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use Livewire\Component;
use Livewire\WithPagination;

final class SimpleConversationsList extends Component
{
    use WithPagination;

    public int $accountId;
    public WhatsAppAccount $account;

    public function mount(int $accountId): void
    {
        $this->accountId = $accountId;
        $this->account = WhatsAppAccount::findOrFail($accountId);

        // Check that the user has access to this account
        if ($this->account->user_id !== auth()->id()) {
            abort(403);
        }
    }

    public function render()
    {
        $conversations = WhatsAppConversation::query()
            ->where('whatsapp_account_id', $this->account->id)
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->orderBy('last_message_at', 'desc')
            ->paginate(10);

        return view('livewire.customer.whats-app.simple-conversations-list', [
            'conversations' => $conversations,
        ]);
    }
}
