<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use PHPUnit\Framework\TestCase;

final class WhatsAppContactNameExtractionTest extends TestCase
{
    /** @test */
    public function it_prioritizes_contact_name_over_public_name()
    {
        $dto = new WhatsAppMessageRequestDTO(
            id: 'test_id',
            from: '237676636794@c.us',
            body: 'Test message',
            timestamp: 1234567890,
            type: 'chat',
            isGroup: false,
            contactName: 'Jean Saved',
            publicName: 'Jean Public'
        );

        $this->assertEquals('Jean Saved', $dto->getBestContactName());
        $this->assertEquals('Jean Public', $dto->getPublicName());
    }

    /** @test */
    public function it_uses_public_name_when_no_saved_contact_name()
    {
        $dto = new WhatsAppMessageRequestDTO(
            id: 'test_id',
            from: '237676636794@c.us',
            body: 'Test message',
            timestamp: 1234567890,
            type: 'chat',
            isGroup: false,
            contactName: null,
            publicName: 'Jean Public'
        );

        $this->assertEquals('Jean Public', $dto->getBestContactName());
        $this->assertEquals('Jean Public', $dto->getPublicName());
    }

    /** @test */
    public function it_falls_back_to_phone_number_when_no_names()
    {
        $dto = new WhatsAppMessageRequestDTO(
            id: 'test_id',
            from: '237676636794@c.us',
            body: 'Test message',
            timestamp: 1234567890,
            type: 'chat',
            isGroup: false,
            contactName: null,
            publicName: null
        );

        $this->assertEquals('237676636794', $dto->getBestContactName());
        $this->assertNull($dto->getPublicName());
    }

    /** @test */
    public function it_creates_from_webhook_with_all_contact_fields()
    {
        $webhookData = [
            'id' => 'test_id',
            'from' => '237676636794@c.us',
            'body' => 'Test message',
            'timestamp' => 1234567890,
            'type' => 'chat',
            'isGroup' => false,
            'contactName' => 'Jean Saved',
            'pushName' => 'Jean Push',
            'publicName' => 'Jean Public',
            'displayName' => 'Jean Display',
        ];

        $dto = WhatsAppMessageRequestDTO::fromWebhookData($webhookData);

        $this->assertEquals('Jean Saved', $dto->contactName);
        $this->assertEquals('Jean Push', $dto->pushName);
        $this->assertEquals('Jean Public', $dto->publicName);
        $this->assertEquals('Jean Display', $dto->displayName);
        $this->assertEquals('Jean Saved', $dto->getBestContactName()); // Priority: saved name first
        $this->assertEquals('Jean Public', $dto->getPublicName()); // Public name
    }
}
