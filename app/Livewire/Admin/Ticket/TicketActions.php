<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Ticket;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class TicketActions extends Component
{
    public Ticket $ticket;

    protected TicketService $ticketService;

    public function boot(TicketService $ticketService): void
    {
        $this->ticketService = $ticketService;
    }

    public function changeStatus(string $status): void
    {
        $this->authorize('changeStatus', $this->ticket);

        $ticketStatus = TicketStatus::make($status);

        $this->ticketService->changeTicketStatus($this->ticket, $ticketStatus);

        session()->flash('success', "Ticket status changed to {$ticketStatus->label}");

        $this->redirect(route('admin.tickets.show', $this->ticket));
    }

    public function changePriority(string $priority): void
    {
        $this->authorize('changePriority', $this->ticket);

        $ticketPriority = TicketPriority::make($priority);

        $this->ticketService->changeTicketPriority($this->ticket, $ticketPriority);

        session()->flash('success', "Ticket priority changed to {$ticketPriority->label}");

        $this->redirect(route('admin.tickets.show', $this->ticket));
    }

    public function assignToMe(): void
    {
        $this->authorize('assign', $this->ticket);

        $this->ticketService->assignTicket($this->ticket, Auth::user());

        $this->ticket->refresh();

        $this->dispatch('ticket-updated');

        session()->flash('success', 'Ticket assigned to you successfully');
    }

    public function render(): mixed
    {
        return view('livewire.admin.ticket.ticket-actions');
    }
}
