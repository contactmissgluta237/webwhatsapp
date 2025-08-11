<?php

declare(strict_types=1);

namespace App\Channels;

use Illuminate\Notifications\Notification;

final class PushNotificationChannel
{
    public function send($notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toPush')) {
            return;
        }

        $notification->toPush($notifiable);
    }
}
