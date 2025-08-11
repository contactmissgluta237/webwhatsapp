<?php

namespace App\Listeners;

use App\Events\UserUpdatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogUserUpdatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(UserUpdatedEvent $event): void
    {
        $user = $event->user;
        $changes = $event->changes;

        Log::channel('user-dynamic')->info("User updated: {$user->full_name} (ID: {$user->id})", [
            'user_id' => $user->id,
            'changes' => $changes,
            'updated_at' => now()->toDateTimeString(),
        ]);
    }
}
