<?php

namespace App\Services;

use App\Enums\TicketPriority;
use App\Enums\TicketSenderType;
use App\Enums\TicketStatus;
use App\Events\TicketCreatedEvent;
use App\Events\TicketMessageSentEvent;
use App\Events\TicketStatusChangedEvent;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\Shared\Media\MediaServiceInterface;
use Illuminate\Support\Facades\DB;

class TicketService extends BaseService
{
    public function __construct(
        protected MediaServiceInterface $mediaService,
    ) {
        parent::__construct($mediaService);
    }

    public function createTicket(
        User $user,
        string $title,
        string $description,
        array $attachments = [],
        ?TicketPriority $priority = null
    ): Ticket {
        return DB::transaction(function () use ($user, $title, $description, $attachments, $priority) {
            $ticket = Ticket::create([
                'user_id' => $user->id,
                'title' => $title,
                'description' => $description,
                'status' => TicketStatus::OPEN(),
                'priority' => $priority ?? TicketPriority::MEDIUM(),
            ]);

            if (! empty($attachments)) {
                $this->mediaService->attachMedia($ticket, $attachments, 'attachments');
            }

            // Create initial message for the ticket
            $ticketMessage = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $description,
                'sender_type' => TicketSenderType::CUSTOMER(),
            ]);

            if (! empty($attachments)) {
                $this->mediaService->attachMedia($ticketMessage, $attachments, 'attachments');
            }

            TicketCreatedEvent::dispatch($ticket->load('user'));

            return $ticket;
        });
    }

    public function replyToTicket(
        Ticket $ticket,
        User $user,
        string $message,
        TicketSenderType $senderType,
        array $attachments = [],
        bool $isInternal = false
    ): TicketMessage {
        return DB::transaction(function () use ($ticket, $user, $message, $senderType, $attachments, $isInternal) {
            $ticketMessage = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $message,
                'sender_type' => $senderType,
                'is_internal' => $isInternal,
            ]);

            if (! empty($attachments)) {
                $this->mediaService->attachMedia($ticketMessage, $attachments, 'attachments');
            }

            // Auto-change status based on who replied
            $this->autoChangeTicketStatus($ticket, $senderType);

            TicketMessageSentEvent::dispatch($ticketMessage->load('user', 'ticket.user'));

            return $ticketMessage;
        });
    }

    private function autoChangeTicketStatus(Ticket $ticket, TicketSenderType $senderType): void
    {
        // When admin replies to an open ticket, change to replied
        if ($senderType->equals(TicketSenderType::ADMIN()) && $ticket->status->equals(TicketStatus::OPEN())) {
            $ticket->status = TicketStatus::REPLIED();
            $ticket->save();
        }

        // When customer replies to a replied ticket, change back to open (needs admin attention)
        if ($senderType->equals(TicketSenderType::CUSTOMER()) && $ticket->status->equals(TicketStatus::REPLIED())) {
            $ticket->status = TicketStatus::OPEN();
            $ticket->save();
        }
    }

    public function changeTicketStatus(Ticket $ticket, TicketStatus $status): Ticket
    {
        return DB::transaction(function () use ($ticket, $status) {
            $ticket->status = $status;
            if ($status->equals(TicketStatus::CLOSED()) || $status->equals(TicketStatus::RESOLVED())) {
                $ticket->closed_at = now();
            }
            $ticket->save();

            TicketStatusChangedEvent::dispatch($ticket->load('user'));

            return $ticket;
        });
    }

    public function assignTicket(Ticket $ticket, User $admin): Ticket
    {
        return DB::transaction(function () use ($ticket, $admin) {
            $ticket->assigned_to = $admin->id;
            $ticket->save();

            return $ticket;
        });
    }

    public function changeTicketPriority(Ticket $ticket, TicketPriority $priority): Ticket
    {
        return DB::transaction(function () use ($ticket, $priority): Ticket {
            $ticket->priority = $priority;
            $ticket->save();

            return $ticket;
        });
    }

    protected function getModel(): string
    {
        return Ticket::class;
    }

    protected function getMediaFields(): array
    {
        return []; // No generic media fields, attachments are handled specifically
    }
}
