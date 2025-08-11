<?php

declare(strict_types=1);

namespace App\Livewire\WhatsApp;

use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Conversations extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedConversation = null;

    protected $paginationTheme = 'bootstrap';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function selectConversation($conversationId)
    {
        $this->selectedConversation = Conversation::find($conversationId);
    }

    public function render()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $conversations = Conversation::whereHas('whatsappAccount', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->with(['whatsappAccount', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('contact_name', 'like', '%'.$this->search.'%')
                        ->orWhere('contact_phone', 'like', '%'.$this->search.'%');
                });
            })
            ->latest('updated_at')
            ->paginate(15);

        return view('livewire.whatsapp.conversations', [
            'conversations' => $conversations,
        ]);
    }
}
