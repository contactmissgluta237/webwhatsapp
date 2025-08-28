<?php

declare(strict_types=1);

namespace Tests\Unit\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\Contracts\AIProviderServiceInterface;
use App\Services\WhatsApp\Contracts\MessageBuildServiceInterface;
use App\Services\WhatsApp\Contracts\WhatsAppMessageOrchestratorInterface;
use App\Services\WhatsApp\Helpers\AIResponseParserHelper;
use App\Services\WhatsApp\Helpers\ResponseTimingHelper;
use App\Services\WhatsApp\WhatsAppMessageOrchestrator;
use Tests\TestCase;

final class WhatsAppMessageOrchestratorTest extends TestCase
{
    public function test_orchestrator_can_be_instantiated(): void
    {
        // Mock all dependencies
        $messageBuildService = $this->createMock(MessageBuildServiceInterface::class);
        $aiProviderService = $this->createMock(AIProviderServiceInterface::class);
        $aiResponseParser = $this->createMock(AIResponseParserHelper::class);
        $responseTimingHelper = $this->createMock(ResponseTimingHelper::class);

        $orchestrator = new WhatsAppMessageOrchestrator(
            $messageBuildService,
            $aiProviderService,
            $aiResponseParser,
            $responseTimingHelper
        );

        $this->assertInstanceOf(WhatsAppMessageOrchestratorInterface::class, $orchestrator);
    }

    public function test_processes_message_with_valid_inputs(): void
    {
        // Arrange
        $messageBuildService = $this->createMock(MessageBuildServiceInterface::class);
        $aiProviderService = $this->createMock(AIProviderServiceInterface::class);
        $aiResponseParser = $this->createMock(AIResponseParserHelper::class);
        $responseTimingHelper = $this->createMock(ResponseTimingHelper::class);

        $orchestrator = new WhatsAppMessageOrchestrator(
            $messageBuildService,
            $aiProviderService,
            $aiResponseParser,
            $responseTimingHelper
        );

        // Create real objects
        $account = WhatsAppAccount::factory()->create([
            'agent_enabled' => false,
        ]);

        $messageRequest = WhatsAppMessageRequestDTO::fromWebhookData([
            'from' => 'user@test.com',
            'body' => 'Hello test',
            'id' => 'msg_123',
            'timestamp' => time(),
            'fromMe' => false,
            'type' => 'chat',
            'isGroup' => false,
        ]);

        $conversationHistory = 'Previous messages';

        // Act - Test that method exists and can be called
        $this->assertTrue(method_exists($orchestrator, 'processMessage'));

        // Basic structure test - when agent is disabled, should return simple response
        $result = $orchestrator->processMessage($account, $messageRequest, $conversationHistory);

        $this->assertInstanceOf(WhatsAppMessageResponseDTO::class, $result);
    }
}
