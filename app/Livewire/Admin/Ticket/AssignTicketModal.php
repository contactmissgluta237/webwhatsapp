<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Ticket;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Livewire\Attributes\On;
use Livewire\Component;

final class AssignTicketModal extends Component
{
    public bool $showModal = false;
    public ?int $ticketId = null;
    public ?int $selectedAdminId = null;

    protected TicketService $ticketService;

    public function boot(TicketService $ticketService): void
    {
        $this->ticketService = $ticketService;
    }

    #[On('show-assign-modal')]
    public function showModal(array $data): void
    {
        $this->ticketId = $data['ticketId'];
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->ticketId = null;
        $this->selectedAdminId = null;
    }

    public function assignTicket(): void
    {
        $this->validate([
            'selectedAdminId' => 'required|exists:users,id',
        ]);

        $ticket = Ticket::findOrFail($this->ticketId);
        $admin = User::findOrFail($this->selectedAdminId);

        $this->authorize('update', $ticket);

        $this->ticketService->assignTicket($ticket, $admin);

        $this->dispatch('ticket-updated');

        session()->flash('success', "Ticket assigned to {$admin->full_name}");

        $this->closeModal();
    }

    public function getAdminsProperty(): mixed
    {
        return User::role('admin')->orderBy('first_name')->get();
    }

    public function render(): mixed
    {
        return view('livewire.admin.ticket.assign-ticket-modal');
    }
}
