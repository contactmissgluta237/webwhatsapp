<?php

namespace App\Livewire\Customer\Ticket;

use App\Http\Requests\Customer\Ticket\CreateTicketRequest;
use App\Services\TicketService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateTicketForm extends Component
{
    use WithFileUploads;

    public string $title = '';
    public string $description = '';
    public array $attachments = [];

    protected TicketService $ticketService;

    public function boot(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    protected function rules(): array
    {
        return (new CreateTicketRequest)->rules();
    }

    protected function messages(): array
    {
        return (new CreateTicketRequest)->messages();
    }

    public function createTicket()
    {
        $this->validate();

        $ticket = $this->ticketService->createTicket(
            Auth::user(),
            $this->title,
            $this->description,
            $this->attachments
        );

        session()->flash('success', 'Ticket created successfully.');

        return redirect()->route('customer.tickets.index');
    }

    public function render()
    {
        return view('livewire.customer.ticket.create-ticket-form');
    }

    public function getAttachmentPreviewUrlsProperty(): array
    {
        $urls = [];
        foreach ($this->attachments as $attachment) {
            if ($attachment instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                $urls[] = $attachment->temporaryUrl();
            }
        }

        return $urls;
    }
}
