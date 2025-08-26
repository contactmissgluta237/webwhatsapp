<?php

namespace App\Listeners;

use App\Notifications\TicketStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyCustomerOfStatusChangeListener extends BaseListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct() {}

    protected function getEventIdentifiers($event): array
    {
        return [
            'ticket_id' => $event->ticket->id,
            'user_id' => $event->ticket->user->id,
            'new_status' => $event->ticket->status->value,
            'event_type' => 'ticket_status_changed',
        ];
    }

    /**
     * Handle the event.
     */
    protected function handleEvent($event): void
    {
        $event->ticket->user->notify(new TicketStatusChangedNotification($event->ticket));
    }
}
