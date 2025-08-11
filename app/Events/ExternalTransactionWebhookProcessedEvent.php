<?php

namespace App\Events;

use App\Models\ExternalTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExternalTransactionWebhookProcessedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ExternalTransaction $transaction;

    /**
     * Create a new event instance.
     */
    public function __construct(ExternalTransaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
