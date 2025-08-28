<?php

declare(strict_types=1);

namespace Tests\Unit\WhatsApp\Services;

use App\DTOs\AI\AiRequestDTO;
use App\Models\WhatsAppAccount;
use App\Services\AI\AiServiceInterface;
use App\Services\WhatsApp\MessageBuildService;
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

        $account = WhatsAppAccount::factory()->create([
            'agent_enabled' => true,
            'agent_prompt' => 'Tu es un assistant WhatsApp professionnel.',
        ]);

        $conversationHistory = 'User: Bonjour\nBot: Bonjour ! Comment puis-je vous aider ?';
        $userMessage = 'Je cherche des informations sur vos produits';

        // Act
        $result = $service->buildAiRequest($account, $conversationHistory, $userMessage);

        // Assert
        $this->assertInstanceOf(AiRequestDTO::class, $result);
        $this->assertEquals($userMessage, $result->userMessage);
        $this->assertNotEmpty($result->systemPrompt);
        $this->assertEquals($account, $result->account);
    }
}
