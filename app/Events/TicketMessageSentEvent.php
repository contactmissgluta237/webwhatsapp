<?php

namespace App\Events;

use App\Models\TicketMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketMessageSentEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TicketMessage $ticketMessage;

    /**
     * Create a new event instance.
     */
    public function __construct(TicketMessage $ticketMessage)
    {
        $this->ticketMessage = $ticketMessage;
    }
}
