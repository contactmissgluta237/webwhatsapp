<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use PHPUnit\Framework\TestCase;

final class WhatsAppMessageRequestDTOTest extends TestCase
{
    /** @test */
    public function it_prioritizes_contact_name_over_push_name()
    {
        $dto = new WhatsAppMessageRequestDTO(
            id: 'test_id',
            from: '237676636794@c.us',
            body: 'Test message',
            timestamp: 1234567890,
            type: 'chat',
            isGroup: false,
            contactName: 'Jean Sauvegardé',
            pushName: 'Jean Public',
            displayName: 'Jean Public'
        );

        $this->assertEquals('Jean Sauvegardé', $dto->getBestContactName());
    }

    /** @test */
    public function it_uses_push_name_when_no_saved_contact_name()
    {
        $dto = new WhatsAppMessageRequestDTO(
            id: 'test_id',
            from: '237676636794@c.us',
            body: 'Test message',
            timestamp: 1234567890,
            type: 'chat',
            isGroup: false,
            contactName: null,
            pushName: 'Jean Public',
            displayName: 'Jean Public'
        );

        $this->assertEquals('Jean Public', $dto->getBestContactName());
    }

    /** @test */
    public function it_uses_display_name_as_fallback()
    {
        $dto = new WhatsAppMessageRequestDTO(
            id: 'test_id',
            from: '237676636794@c.us',
            body: 'Test message',
            timestamp: 1234567890,
            type: 'chat',
            isGroup: false,
            contactName: null,
            pushName: null,
            displayName: 'Jean Display'
        );

        $this->assertEquals('Jean Display', $dto->getBestContactName());
    }

    /** @test */
    public function it_falls_back_to_phone_number_when_no_names_available()
    {
        $dto = new WhatsAppMessageRequestDTO(
            id: 'test_id',
            from: '237676636794@c.us',
            body: 'Test message',
            timestamp: 1234567890,
            type: 'chat',
            isGroup: false,
            contactName: null,
            pushName: null,
            displayName: null
        );

        $this->assertEquals('237676636794', $dto->getBestContactName());
    }

    /** @test */
    public function it_creates_from_webhook_data_correctly()
    {
        $webhookData = [
            'id' => 'test_id',
            'from' => '237676636794@c.us',
            'body' => 'Test message',
            'timestamp' => 1234567890,
            'type' => 'chat',
            'isGroup' => false,
            'contactName' => 'Jean Sauvegardé',
            'pushName' => 'Jean Public',
            'displayName' => 'Jean Public',
        ];

        $dto = WhatsAppMessageRequestDTO::fromWebhookData($webhookData);

        $this->assertEquals('test_id', $dto->id);
        $this->assertEquals('237676636794@c.us', $dto->from);
        $this->assertEquals('Jean Sauvegardé', $dto->contactName);
        $this->assertEquals('Jean Public', $dto->pushName);
        $this->assertEquals('Jean Public', $dto->displayName);
        $this->assertEquals('Jean Sauvegardé', $dto->getBestContactName());
    }

    /** @test */
    public function it_handles_missing_contact_fields_in_webhook_data()
    {
        $webhookData = [
            'id' => 'test_id',
            'from' => '237676636794@c.us',
            'body' => 'Test message',
            'timestamp' => 1234567890,
            'type' => 'chat',
            'isGroup' => false,
            // contactName, pushName, displayName not provided
        ];

        $dto = WhatsAppMessageRequestDTO::fromWebhookData($webhookData);

        $this->assertNull($dto->contactName);
        $this->assertNull($dto->pushName);
        $this->assertNull($dto->displayName);
        $this->assertEquals('237676636794', $dto->getBestContactName()); // Falls back to phone number
    }
}
