<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\WhatsAppAccount;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WhatsAppAccountDisconnectedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public WhatsAppAccount $account
    ) {}
}