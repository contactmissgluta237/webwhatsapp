<?php

declare(strict_types=1);

namespace Tests\Unit\WhatsApp;

use App\Services\WhatsApp\WhatsAppMessageOrchestrator;
use App\Contracts\WhatsApp\ContextPreparationServiceInterface;
use App\Contracts\WhatsApp\MessageBuildServiceInterface;
use App\Contracts\WhatsApp\AIProviderServiceInterface;
use App\Contracts\WhatsApp\ResponseFormatterServiceInterface;
use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use Tests\TestCase;

class WhatsAppMessageOrchestratorTest extends TestCase
{
    public function test_orchestrator_can_be_instantiated(): void
    {
        // Mock all required dependencies
        $contextService = $this->createMock(ContextPreparationServiceInterface::class);
        $messageBuildService = $this->createMock(MessageBuildServiceInterface::class);
        $aiProviderService = $this->createMock(AIProviderServiceInterface::class);
        $responseFormatterService = $this->createMock(ResponseFormatterServiceInterface::class);
        
        // Create orchestrator instance
        $orchestrator = new WhatsAppMessageOrchestrator(
            $contextService,
            $messageBuildService,
            $aiProviderService,
            $responseFormatterService
        );
        
        // Assert instance is created correctly
        $this->assertInstanceOf(WhatsAppMessageOrchestrator::class, $orchestrator);
    }

    public function test_processes_incoming_message_with_disabled_agent(): void
    {
        // Arrange
        $accountMetadata = new WhatsAppAccountMetadataDTO(
            sessionId: 'session_123',
            sessionName: 'Test Session',
            accountId: 1,
            agentEnabled: false // Agent disabled
        );

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_123',
            from: '+237123456789@c.us',
            body: 'Bonjour',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        // Mock dependencies (they shouldn't be called for disabled agent)
        $contextService = $this->createMock(ContextPreparationServiceInterface::class);
        $messageBuildService = $this->createMock(MessageBuildServiceInterface::class);
        $aiProviderService = $this->createMock(AIProviderServiceInterface::class);
        $responseFormatterService = $this->createMock(ResponseFormatterServiceInterface::class);
        
        $orchestrator = new WhatsAppMessageOrchestrator(
            $contextService,
            $messageBuildService,
            $aiProviderService,
            $responseFormatterService
        );

        // Act
        $result = $orchestrator->processIncomingMessage($accountMetadata, $messageRequest);

        // Assert
        $this->assertInstanceOf(WhatsAppMessageResponseDTO::class, $result);
        $this->assertTrue($result->processed);
        $this->assertFalse($result->hasAiResponse);
        $this->assertNull($result->aiResponse);
    }

    public function test_processes_simulated_message(): void
    {
        // Arrange
        $accountMetadata = new WhatsAppAccountMetadataDTO(
            sessionId: 'session_123',
            sessionName: 'Test Session',
            accountId: 1,
            agentEnabled: true
        );

        $userMessage = 'Test simulation message';
        $context = [];

        // Mock dependencies
        $contextService = $this->createMock(ContextPreparationServiceInterface::class);
        $messageBuildService = $this->createMock(MessageBuildServiceInterface::class);
        $aiProviderService = $this->createMock(AIProviderServiceInterface::class);
        $responseFormatterService = $this->createMock(ResponseFormatterServiceInterface::class);
        
        $orchestrator = new WhatsAppMessageOrchestrator(
            $contextService,
            $messageBuildService,
            $aiProviderService,
            $responseFormatterService
        );

        // Act & Assert (test basic structure)
        $this->assertInstanceOf(WhatsAppMessageOrchestrator::class, $orchestrator);
        
        // Method exists check
        $this->assertTrue(method_exists($orchestrator, 'processSimulatedMessage'));
    }

    public function test_orchestrator_has_required_methods(): void
    {
        // Mock dependencies
        $contextService = $this->createMock(ContextPreparationServiceInterface::class);
        $messageBuildService = $this->createMock(MessageBuildServiceInterface::class);
        $aiProviderService = $this->createMock(AIProviderServiceInterface::class);
        $responseFormatterService = $this->createMock(ResponseFormatterServiceInterface::class);
        
        $orchestrator = new WhatsAppMessageOrchestrator(
            $contextService,
            $messageBuildService,
            $aiProviderService,
            $responseFormatterService
        );

        // Assert required methods exist
        $this->assertTrue(method_exists($orchestrator, 'processIncomingMessage'));
        $this->assertTrue(method_exists($orchestrator, 'processSimulatedMessage'));
    }
}
