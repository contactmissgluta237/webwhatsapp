<?php

declare(strict_types=1);

namespace Tests\Unit\WhatsApp\Services;

use App\Services\WhatsApp\MessageBuildService;
use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\DTOs\WhatsApp\ConversationContextDTO;
use App\DTOs\AI\AiRequestDTO;
use App\Services\AI\AiServiceInterface;
use Tests\TestCase;

class MessageBuildServiceTest extends TestCase
{
    public function test_service_can_be_instantiated(): void
    {
        $aiService = $this->createMock(AiServiceInterface::class);
        $service = new MessageBuildService($aiService);
        
        $this->assertInstanceOf(MessageBuildService::class, $service);
    }

    public function test_builds_ai_request_with_valid_inputs(): void
    {
        // Arrange
        $aiService = $this->createMock(AiServiceInterface::class);
        $service = new MessageBuildService($aiService);

        $accountMetadata = new WhatsAppAccountMetadataDTO(
            sessionId: 'session_123',
            sessionName: 'Test Session',
            accountId: 1,
            agentEnabled: true
        );

        $conversationContext = new ConversationContextDTO(
            conversationId: 1,
            chatId: 'test@c.us',
            contactPhone: '+237123456789',
            isGroup: false,
            recentMessages: [],
            contextualInformation: 'Test context',
            metadata: []
        );

        $userMessage = 'Bonjour';

        // Act & Assert - Test that method exists and can be called
        $this->assertTrue(method_exists($service, 'buildAiRequest'));
        
        // Basic structure test
        $result = $service->buildAiRequest($accountMetadata, $conversationContext, $userMessage);
        
        $this->assertInstanceOf(AiRequestDTO::class, $result);
    }

    public function test_service_has_required_methods(): void
    {
        $aiService = $this->createMock(AiServiceInterface::class);
        $service = new MessageBuildService($aiService);

        $this->assertTrue(method_exists($service, 'buildAiRequest'));
    }
}
