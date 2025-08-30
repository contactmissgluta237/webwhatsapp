<?php

declare(strict_types=1);

namespace Tests\Unit\Services\WhatsApp;

use App\Services\WhatsApp\WhatsAppNotificationHandler;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class WhatsAppNotificationHandlerTest extends TestCase
{
    public function test_send_notification_success(): void
    {
        Http::fake([
            '*/api/bridge/send-message' => Http::response(['success' => true], 200),
        ]);

        $handler = new WhatsAppNotificationHandler;

        $result = $handler->sendNotification(
            'session123',
            '+237676636794',
            'Test message'
        );

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost:3000/api/bridge/send-message' &&
                   $request['session_id'] === 'session123' &&
                   $request['to'] === '+237676636794@c.us' &&
                   $request['message'] === 'Test message';
        });
    }

    public function test_send_notification_failure(): void
    {
        Http::fake([
            '*/api/bridge/send-message' => Http::response(['error' => 'Failed'], 500),
        ]);

        $handler = new WhatsAppNotificationHandler;

        $result = $handler->sendNotification(
            'session123',
            '+237676636794',
            'Test message'
        );

        $this->assertFalse($result);
    }
}
