<?php

namespace App\Livewire\Admin\Ticket;

use App\Enums\TicketSenderType;
use App\Http\Requests\Admin\Ticket\ReplyTicketRequest;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class ReplyTicketForm extends Component
{
    use WithFileUploads;

    public Ticket $ticket;
    public string $message = '';
    public array $attachments = [];
    public bool $isInternal = false;

    protected TicketService $ticketService;

    public function boot(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    protected function customRequest(): FormRequest
    {
        return new ReplyTicketRequest;
    }

    public function rules(): array
    {
        // @phpstan-ignore-next-line
        return $this->customRequest()->rules();
    }

    public function messages(): array
    {
        return $this->customRequest()->messages();
    }

    public function replyToTicket()
    {
        $this->validate();

        $this->ticketService->replyToTicket(
            $this->ticket,
            Auth::user(),
            $this->message,
            TicketSenderType::ADMIN(),
            $this->attachments,
            $this->isInternal
        );

        session()->flash('success', 'Reply sent successfully.');

        return redirect()->route('admin.tickets.show', $this->ticket);
    }

    public function render()
    {
        return view('livewire.admin.ticket.reply-ticket-form');
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
