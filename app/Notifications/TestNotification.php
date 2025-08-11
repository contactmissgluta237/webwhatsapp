<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\TestNotificationMail;
use App\Models\User;
use App\Services\PushNotificationService;
use App\Services\TestNotificationFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class TestNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly PushNotificationService $pushService,
        private readonly TestNotificationFactory $notificationFactory
    ) {}

    /**
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'push', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'test',
            'message' => 'This is a test notification.',
            'url' => '/',
        ];
    }

    public function toMail(object $notifiable): TestNotificationMail
    {
        assert($notifiable instanceof User);

        return (new TestNotificationMail)->to($notifiable->email);
    }

    public function toPush(object $notifiable): void
    {
        assert($notifiable instanceof User);

        $notification = $this->notificationFactory->createTestNotification();
        $this->pushService->sendNotification($notifiable, $notification);
    }
}
