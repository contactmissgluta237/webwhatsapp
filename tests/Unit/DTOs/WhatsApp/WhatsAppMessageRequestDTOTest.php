<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use PHPUnit\Framework\TestCase;

final class WhatsAppMessageRequestDTOTest extends TestCase
{
    /**
     * Test la création d'un DTO à partir de données webhook complètes
     */
    public function test_from_webhook_data_creates_dto_correctly(): void
    {
        // Arrange
        $webhookData = [
            'id' => 'msg_123456789',
            'from' => '+237123456789@c.us',
            'body' => 'Bonjour, je voudrais des informations sur vos produits',
            'timestamp' => 1692705600,
            'type' => 'text',
            'isGroup' => false,
            'chatName' => 'Contact Test',
            'metadata' => [
                'deviceType' => 'android',
                'hasMedia' => false
            ]
        ];

        // Act
        $dto = WhatsAppMessageRequestDTO::fromWebhookData($webhookData);

        // Assert
        $this->assertSame('msg_123456789', $dto->id);
        $this->assertSame('+237123456789@c.us', $dto->from);
        $this->assertSame('Bonjour, je voudrais des informations sur vos produits', $dto->body);
        $this->assertSame(1692705600, $dto->timestamp);
        $this->assertSame('text', $dto->type);
        $this->assertFalse($dto->isGroup);
        $this->assertSame('Contact Test', $dto->chatName);
        $this->assertSame([
            'deviceType' => 'android',
            'hasMedia' => false
        ], $dto->metadata);
    }

    /**
     * Test la méthode getChatId retourne le champ from
     */
    public function test_get_chat_id_returns_from_field(): void
    {
        // Test avec chat privé
        $privateMessageData = [
            'id' => 'msg_private',
            'from' => '+237123456789@c.us',
            'body' => 'Message privé',
            'timestamp' => 1692705600,
            'type' => 'text',
            'isGroup' => false,
        ];

        $privateDto = WhatsAppMessageRequestDTO::fromWebhookData($privateMessageData);
        $this->assertSame('+237123456789@c.us', $privateDto->getChatId());

        // Test avec chat de groupe
        $groupMessageData = [
            'id' => 'msg_group',
            'from' => 'group123@g.us',
            'body' => 'Message de groupe',
            'timestamp' => 1692705600,
            'type' => 'text',
            'isGroup' => true,
        ];

        $groupDto = WhatsAppMessageRequestDTO::fromWebhookData($groupMessageData);
        $this->assertSame('group123@g.us', $groupDto->getChatId());
    }

    /**
     * Test la méthode getContactPhone supprime les suffixes WhatsApp
     */
    public function test_get_contact_phone_removes_whatsapp_suffixes(): void
    {
        // Test avec numéro privé (@c.us)
        $privateMessageData = [
            'id' => 'msg_private',
            'from' => '+237123456789@c.us',
            'body' => 'Message privé',
            'timestamp' => 1692705600,
            'type' => 'text',
            'isGroup' => false,
        ];

        $privateDto = WhatsAppMessageRequestDTO::fromWebhookData($privateMessageData);
        $this->assertSame('+237123456789', $privateDto->getContactPhone());

        // Test avec groupe (@g.us)
        $groupMessageData = [
            'id' => 'msg_group',
            'from' => 'group123@g.us',
            'body' => 'Message de groupe',
            'timestamp' => 1692705600,
            'type' => 'text',
            'isGroup' => true,
        ];

        $groupDto = WhatsAppMessageRequestDTO::fromWebhookData($groupMessageData);
        $this->assertSame('group123', $groupDto->getContactPhone());
    }

    /**
     * Test la méthode isFromGroup détecte correctement les messages de groupe
     */
    public function test_is_from_group_detects_group_messages(): void
    {
        // Test message privé
        $privateMessageData = [
            'id' => 'msg_private',
            'from' => '+237123456789@c.us',
            'body' => 'Message privé',
            'timestamp' => 1692705600,
            'type' => 'text',
            'isGroup' => false,
        ];

        $privateDto = WhatsAppMessageRequestDTO::fromWebhookData($privateMessageData);
        $this->assertFalse($privateDto->isFromGroup());

        // Test message de groupe
        $groupMessageData = [
            'id' => 'msg_group',
            'from' => 'group123@g.us',
            'body' => 'Message de groupe',
            'timestamp' => 1692705600,
            'type' => 'text',
            'isGroup' => true,
        ];

        $groupDto = WhatsAppMessageRequestDTO::fromWebhookData($groupMessageData);
        $this->assertTrue($groupDto->isFromGroup());
    }

    /**
     * Test la création avec données minimales (champs optionnels null)
     */
    public function test_from_webhook_data_with_minimal_data(): void
    {
        // Arrange
        $minimalData = [
            'id' => 'msg_minimal',
            'from' => '+237123456789@c.us',
            'body' => 'Message minimal',
            'timestamp' => 1692705600,
            'type' => 'text',
            'isGroup' => false,
            // chatName et metadata absents
        ];

        // Act
        $dto = WhatsAppMessageRequestDTO::fromWebhookData($minimalData);

        // Assert
        $this->assertSame('msg_minimal', $dto->id);
        $this->assertSame('+237123456789@c.us', $dto->from);
        $this->assertSame('Message minimal', $dto->body);
        $this->assertSame(1692705600, $dto->timestamp);
        $this->assertSame('text', $dto->type);
        $this->assertFalse($dto->isGroup);
        $this->assertNull($dto->chatName);
        $this->assertSame([], $dto->metadata);
    }

    /**
     * Test la gestion des caractères spéciaux et émojis
     */
    public function test_handles_special_characters_and_emojis(): void
    {
        // Arrange
        $dataWithSpecialChars = [
            'id' => 'msg_special',
            'from' => '+237123456789@c.us',
            'body' => 'Message avec émojis 😀🎉 et caractères spéciaux: àéèçù "quotes" & <tags>',
            'timestamp' => 1692705600,
            'type' => 'text',
            'isGroup' => false,
        ];

        // Act
        $dto = WhatsAppMessageRequestDTO::fromWebhookData($dataWithSpecialChars);

        // Assert
        $this->assertSame('Message avec émojis 😀🎉 et caractères spéciaux: àéèçù "quotes" & <tags>', $dto->body);
    }

    /**
     * Test avec différents types de messages
     */
    public function test_different_message_types(): void
    {
        // Test message image
        $imageData = [
            'id' => 'msg_image',
            'from' => '+237123456789@c.us',
            'body' => '',
            'timestamp' => 1692705600,
            'type' => 'image',
            'isGroup' => false,
            'metadata' => ['hasMedia' => true]
        ];

        $imageDto = WhatsAppMessageRequestDTO::fromWebhookData($imageData);
        $this->assertSame('image', $imageDto->type);

        // Test message audio
        $audioData = [
            'id' => 'msg_audio',
            'from' => '+237123456789@c.us',
            'body' => '',
            'timestamp' => 1692705600,
            'type' => 'audio',
            'isGroup' => false,
            'metadata' => ['hasMedia' => true]
        ];

        $audioDto = WhatsAppMessageRequestDTO::fromWebhookData($audioData);
        $this->assertSame('audio', $audioDto->type);
    }

    /**
     * Test avec timestamp très ancien et très récent
     */
    public function test_with_different_timestamps(): void
    {
        // Timestamp ancien (2020)
        $oldData = [
            'id' => 'msg_old',
            'from' => '+237123456789@c.us',
            'body' => 'Message ancien',
            'timestamp' => 1577836800, // 2020-01-01
            'type' => 'text',
            'isGroup' => false,
        ];

        $oldDto = WhatsAppMessageRequestDTO::fromWebhookData($oldData);
        $this->assertSame(1577836800, $oldDto->timestamp);

        // Timestamp récent (2025)
        $recentData = [
            'id' => 'msg_recent',
            'from' => '+237123456789@c.us',
            'body' => 'Message récent',
            'timestamp' => 1724356800, // 2024-08-22
            'type' => 'text',
            'isGroup' => false,
        ];

        $recentDto = WhatsAppMessageRequestDTO::fromWebhookData($recentData);
        $this->assertSame(1724356800, $recentDto->timestamp);
    }

    /**
     * Test avec métadonnées complexes
     */
    public function test_with_complex_metadata(): void
    {
        // Arrange
        $complexMetadata = [
            'id' => 'msg_complex_meta',
            'from' => '+237123456789@c.us',
            'body' => 'Message avec métadonnées complexes',
            'timestamp' => 1692705600,
            'type' => 'text',
            'isGroup' => false,
            'metadata' => [
                'deviceType' => 'android',
                'hasMedia' => false,
                'quotedMessage' => [
                    'id' => 'quoted_msg_123',
                    'body' => 'Message cité'
                ],
                'location' => [
                    'latitude' => 3.8480,
                    'longitude' => 11.5021
                ],
                'contacts' => [
                    ['name' => 'John Doe', 'phone' => '+237987654321']
                ]
            ]
        ];

        // Act
        $dto = WhatsAppMessageRequestDTO::fromWebhookData($complexMetadata);

        // Assert
        $this->assertIsArray($dto->metadata);
        $this->assertArrayHasKey('deviceType', $dto->metadata);
        $this->assertArrayHasKey('quotedMessage', $dto->metadata);
        $this->assertArrayHasKey('location', $dto->metadata);
        $this->assertArrayHasKey('contacts', $dto->metadata);
        $this->assertSame('android', $dto->metadata['deviceType']);
        $this->assertSame(3.8480, $dto->metadata['location']['latitude']);
    }
}
