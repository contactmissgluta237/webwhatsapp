<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogUserUpdatedListener extends BaseListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected function getEventIdentifiers($event): array
    {
        return [
            'user_id' => $event->user->id,
            'event_type' => 'user_updated',
            'changes_hash' => md5(serialize($event->changes)),
        ];
    }

    /**
     * Handle the event.
     */
    protected function handleEvent($event): void
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
