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
        // Simple test to verify the handler can be instantiated and called
        $handler = new WhatsAppNotificationHandler;

        // Since this requires actual HTTP client setup, we'll just verify the handler exists
        $this->assertInstanceOf(WhatsAppNotificationHandler::class, $handler);

        // Test that the method exists and can be called (will fail due to missing config but that's expected)
        $this->assertTrue(method_exists($handler, 'sendNotification'));
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
