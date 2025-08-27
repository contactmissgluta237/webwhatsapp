<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppSessionStatusDTO;
use App\Services\WhatsApp\WhatsAppQRService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class WhatsAppQRServiceTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppQRService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WhatsAppQRService::class);
    }

    public function test_get_session_status_returns_dto_when_connected(): void
    {
        $sessionId = 'session_2_17562223624561_43f7dfe4';
        $nodeResponse = [
            'sessionId' => $sessionId,
            'status' => 'connected',
            'lastActivity' => '2025-08-26T15:32:42.476Z',
            'userId' => 2,
            'phoneNumber' => '23755332183',
            'qrCode' => null,
        ];

        Http::fake([
            '*/api/sessions/*/status' => Http::response($nodeResponse, 200),
        ]);

        $result = $this->service->getSessionStatus($sessionId);

        $this->assertInstanceOf(WhatsAppSessionStatusDTO::class, $result);
        $this->assertEquals($sessionId, $result->sessionId);
        $this->assertEquals('connected', $result->status);
        $this->assertEquals('23755332183', $result->phoneNumber);
        $this->assertTrue($result->isConnected());
        $this->assertTrue($result->hasPhoneNumber());
    }

    public function test_get_session_status_returns_null_on_failure(): void
    {
        Http::fake([
            '*/api/sessions/*/status' => Http::response(null, 404),
        ]);

        $result = $this->service->getSessionStatus('non_existent_session');

        $this->assertNull($result);
    }

    public function test_check_session_connection_returns_true_when_connected(): void
    {
        $sessionId = 'test_session';
        Http::fake([
            '*/api/sessions/*/status' => Http::response([
                'sessionId' => $sessionId,
                'status' => 'connected',
                'phoneNumber' => '123456789',
            ], 200),
        ]);

        $isConnected = $this->service->checkSessionConnection($sessionId);

        $this->assertTrue($isConnected);
    }

    public function test_check_session_connection_returns_false_when_disconnected(): void
    {
        $sessionId = 'test_session';
        Http::fake([
            '*/api/sessions/*/status' => Http::response([
                'sessionId' => $sessionId,
                'status' => 'disconnected',
            ], 200),
        ]);

        $isConnected = $this->service->checkSessionConnection($sessionId);

        $this->assertFalse($isConnected);
    }

    public function test_check_session_connection_returns_false_on_error(): void
    {
        Http::fake([
            '*/api/sessions/*/status' => Http::response(null, 500),
        ]);

        $isConnected = $this->service->checkSessionConnection('error_session');

        $this->assertFalse($isConnected);
    }

    public function test_get_session_status_handles_missing_phone_number(): void
    {
        $sessionId = 'session_without_phone';
        Http::fake([
            '*/api/sessions/*/status' => Http::response([
                'sessionId' => $sessionId,
                'status' => 'connected',
                'userId' => 2,
            ], 200),
        ]);

        $result = $this->service->getSessionStatus($sessionId);

        $this->assertNotNull($result);
        $this->assertNull($result->phoneNumber);
        $this->assertFalse($result->hasPhoneNumber());
        $this->assertTrue($result->isConnected());
    }

    public function test_get_session_status_handles_network_timeout(): void
    {
        Http::fake(function () {
            throw new \Exception('Network timeout');
        });

        $result = $this->service->getSessionStatus('timeout_session');

        $this->assertNull($result);
    }
}
