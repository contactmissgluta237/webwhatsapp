<?php

declare(strict_types=1);

namespace Tests\Unit\WhatsApp\DTOs;

use App\DTOs\WhatsApp\WhatsAppSessionStatusDTO;
use Carbon\Carbon;
use Tests\TestCase;

final class WhatsAppSessionStatusDTOTest extends TestCase
{
    public function test_can_create_dto_from_node_js_response(): void
    {
        $nodeResponse = [
            'sessionId' => 'session_2_17562223624561_43f7dfe4',
            'status' => 'connected',
            'phoneNumber' => '23755332183',
            'lastActivity' => '2025-08-26T15:32:42.476Z',
            'userId' => 2,
            'qrCode' => null,
        ];

        $dto = WhatsAppSessionStatusDTO::fromNodeJsResponse($nodeResponse);

        $this->assertInstanceOf(WhatsAppSessionStatusDTO::class, $dto);
        $this->assertEquals('session_2_17562223624561_43f7dfe4', $dto->sessionId);
        $this->assertEquals('connected', $dto->status);
        $this->assertEquals('23755332183', $dto->phoneNumber);
        $this->assertEquals(2, $dto->userId);
        $this->assertNull($dto->qrCode);
        $this->assertTrue($dto->isConnected());
        $this->assertTrue($dto->hasPhoneNumber());
        $this->assertInstanceOf(Carbon::class, $dto->lastActivity);
    }

    public function test_handles_missing_optional_fields(): void
    {
        $nodeResponse = [
            'sessionId' => 'session_test_123',
            'status' => 'disconnected',
        ];

        $dto = WhatsAppSessionStatusDTO::fromNodeJsResponse($nodeResponse);

        $this->assertEquals('session_test_123', $dto->sessionId);
        $this->assertEquals('disconnected', $dto->status);
        $this->assertNull($dto->phoneNumber);
        $this->assertNull($dto->userId);
        $this->assertNull($dto->lastActivity);
        $this->assertNull($dto->qrCode);
        $this->assertFalse($dto->isConnected());
        $this->assertFalse($dto->hasPhoneNumber());
    }

    public function test_correctly_identifies_connected_status(): void
    {
        $connectedResponse = [
            'sessionId' => 'test',
            'status' => 'connected',
        ];

        $disconnectedResponse = [
            'sessionId' => 'test',
            'status' => 'disconnected',
        ];

        $connectingResponse = [
            'sessionId' => 'test',
            'status' => 'connecting',
        ];

        $connectedDto = WhatsAppSessionStatusDTO::fromNodeJsResponse($connectedResponse);
        $disconnectedDto = WhatsAppSessionStatusDTO::fromNodeJsResponse($disconnectedResponse);
        $connectingDto = WhatsAppSessionStatusDTO::fromNodeJsResponse($connectingResponse);

        $this->assertTrue($connectedDto->isConnected());
        $this->assertFalse($disconnectedDto->isConnected());
        $this->assertFalse($connectingDto->isConnected());
    }

    public function test_has_phone_number_detection(): void
    {
        $withPhone = WhatsAppSessionStatusDTO::fromNodeJsResponse([
            'sessionId' => 'test',
            'status' => 'connected',
            'phoneNumber' => '123456789',
        ]);

        $withoutPhone = WhatsAppSessionStatusDTO::fromNodeJsResponse([
            'sessionId' => 'test',
            'status' => 'connected',
        ]);

        $emptyPhone = WhatsAppSessionStatusDTO::fromNodeJsResponse([
            'sessionId' => 'test',
            'status' => 'connected',
            'phoneNumber' => '',
        ]);

        $this->assertTrue($withPhone->hasPhoneNumber());
        $this->assertFalse($withoutPhone->hasPhoneNumber());
        $this->assertFalse($emptyPhone->hasPhoneNumber());
    }

    public function test_to_array_returns_correct_data(): void
    {
        $dto = new WhatsAppSessionStatusDTO(
            sessionId: 'test_123',
            status: 'connected',
            phoneNumber: '987654321',
            lastActivity: Carbon::parse('2025-08-26T12:00:00Z'),
            userId: 5,
            qrCode: 'qr_code_data',
            isConnected: true
        );

        $array = $dto->toArray();

        $this->assertArrayHasKey('sessionId', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('phoneNumber', $array);
        $this->assertArrayHasKey('lastActivity', $array);
        $this->assertArrayHasKey('userId', $array);
        $this->assertArrayHasKey('qrCode', $array);
        $this->assertArrayHasKey('isConnected', $array);
    }
}
