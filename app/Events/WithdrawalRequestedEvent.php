<?php

namespace App\Events;

use App\Models\ExternalTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WithdrawalRequestedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExternalTransaction $transaction) {}
}
