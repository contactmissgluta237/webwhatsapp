<?php

namespace App\Listeners;

use App\Events\TicketStatusChangedEvent;
use App\Notifications\TicketStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyCustomerOfStatusChangeListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(TicketStatusChangedEvent $event): void
    {
        $event->ticket->user->notify(new TicketStatusChangedNotification($event->ticket));
    }
}
