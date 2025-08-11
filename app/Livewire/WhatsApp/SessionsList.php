<?php

declare(strict_types=1);

namespace App\Livewire\WhatsApp;

use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SessionsList extends Component
{
    public $sessions = [];

    public function mount()
    {
        $this->loadSessions();
    }

    public function loadSessions()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $accounts = $user->whatsAppAccounts()->get();

        $this->sessions = $accounts->map(function (WhatsAppAccount $account) {
            return [
                'id' => $account->id,
                'session_name' => $account->session_name,
                'phone_number' => $account->phone_number,
                'status' => $account->status->value,
                'status_label' => $account->status->label,
                'last_seen_at' => $account->last_seen_at,
                'created_at' => $account->created_at,
            ];
        })->toArray();
    }

    public function disconnectSession(string $sessionName)
    {
        try {
            // Update status in database
            WhatsAppAccount::where('session_name', $sessionName)
                ->where('user_id', Auth::id())
                ->update([
                    'status' => 'disconnected',
                    'last_seen_at' => now(),
                ]);

            $this->loadSessions();
            session()->flash('message', 'Session WhatsApp déconnectée avec succès.');

        } catch (\Exception $e) {
            session()->flash('error', 'Erreur: '.$e->getMessage());
        }
    }

    public function refreshSessions()
    {
        $this->loadSessions();
        session()->flash('message', 'Liste des sessions actualisée.');
    }

    public function render()
    {
        return view('livewire.whatsapp.sessions-list');
    }
}
