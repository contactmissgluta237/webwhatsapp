<?php

declare(strict_types=1);

namespace App\Livewire\Customer\Ticket;

use App\Enums\TicketSenderType;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

final class ReplyTicketForm extends Component
{
    use WithFileUploads;

    public Ticket $ticket;
    public string $message = '';
    public array $attachments = [];

    protected TicketService $ticketService;

    public function boot(TicketService $ticketService): void
    {
        $this->ticketService = $ticketService;
    }

    protected function rules(): array
    {
        return [
            'message' => 'required|string',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ];
    }

    public function replyToTicket(): void
    {
        $this->validate();

        $this->ticketService->replyToTicket(
            $this->ticket,
            Auth::user(),
            $this->message,
            TicketSenderType::CUSTOMER(),
            $this->attachments
        );

        session()->flash('success', 'Reply sent successfully.');

        $this->redirect(route('customer.tickets.show', $this->ticket));
    }

    public function render(): mixed
    {
        return view('livewire.customer.ticket.reply-ticket-form');
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
