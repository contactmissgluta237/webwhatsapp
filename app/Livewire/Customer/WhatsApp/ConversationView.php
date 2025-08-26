<?php

declare(strict_types=1);

namespace App\Livewire\Customer\WhatsApp;

use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

final class ConversationView extends Component
{
    use WithPagination;

    public WhatsAppAccount $account;
    public WhatsAppConversation $conversation;

    public function mount(WhatsAppAccount $account, WhatsAppConversation $conversation): void
    {
        $this->account = $account;
        $this->conversation = $conversation;

        // Marquer la conversation comme lue
        if ($this->conversation->unread_count > 0) {
            $this->conversation->markAsRead();
            Log::info('Conversation marquée comme lue', [
                'conversation_id' => $this->conversation->id,
                'account_id' => $this->account->id,
            ]);
        }
    }

    public function getMessages()
    {
        return $this->conversation->messages()
            ->orderBy('created_at', 'asc')
            ->paginate(50);
    }

    public function loadMore(): void
    {
        $this->setPage($this->getPage() + 1);
    }

    public function render()
    {
        $messages = $this->getMessages();

        return view('livewire.customer.whats-app.conversation-view', [
            'messages' => $messages,
            'conversationStats' => $this->conversation->getTodayMessagesCount(),
        ]);
    }
}
