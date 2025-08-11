<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\PushNotificationDTO;

final class TestNotificationFactory
{
    public function createTestNotification(): PushNotificationDTO
    {
        return new PushNotificationDTO(
            title: '🧪 Test Push Notification',
            body: 'Great! Push notifications are working correctly on your device.',
            icon: '/favicon.ico',
            badge: '/favicon.ico',
            tag: 'test-notification-'.time(),
            data: [
                'test' => true,
                'timestamp' => time(),
                'url' => url('/'),
            ],
            actions: [
                [
                    'action' => 'view',
                    'title' => '👀 View App',
                    'icon' => '/favicon.ico',
                ],
                [
                    'action' => 'dismiss',
                    'title' => '❌ Dismiss',
                    'icon' => '/favicon.ico',
                ],
            ]
        );
    }
}
