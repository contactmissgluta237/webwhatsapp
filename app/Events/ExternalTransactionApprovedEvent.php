<?php

namespace App\Events;

use App\Models\ExternalTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExternalTransactionApprovedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExternalTransaction $transaction) {}
}
