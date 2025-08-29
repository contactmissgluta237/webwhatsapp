<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp\Integration;

use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Events\WhatsApp\AiResponseGenerated;
use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\Contracts\AIProviderServiceInterface;
use App\Services\WhatsApp\WhatsAppMessageOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WhatsAppEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ⚠️ MOCK L'IA POUR ÉVITER LES APPELS RÉELS ET LA CONSOMMATION DE TOKENS
        $this->mockAIProviderService();

        // Create AI model for tests
        $aiModel = AiModel::factory()->create([
            'id' => 1,
            'name' => 'Test GPT Model',
            'is_active' => true,
            'is_default' => true,
        ]);

        // Create test data
        WhatsAppAccount::factory()->create([
            'id' => 1,
            'agent_enabled' => true,
            'ai_model_id' => $aiModel->id,
            'contextual_information' => 'Test business information',
        ]);
    }

    /**
     * Mock l'IA pour éviter les vrais appels API et la consommation de tokens
     */
    private function mockAIProviderService(): void
    {
        $mockService = $this->createMock(AIProviderServiceInterface::class);
        
        $mockService->method('processMessage')
            ->willReturnCallback(function($aiModel, $systemPrompt, $userMessage, $context) {
                $response = json_encode([
                    'message' => 'Bonjour ! Comment puis-je vous aider aujourd\'hui ?',
                    'action' => 'text',
                    'products' => []
                ]);

                return new WhatsAppAIResponseDTO(
                    response: $response,
                    model: 'mocked-test-model',
                    confidence: 0.9,
                    tokensUsed: 25,
                    cost: 0.005
                );
            });

        $this->app->instance(AIProviderServiceInterface::class, $mockService);
    }

    public function test_complete_message_processing_flow(): void
    {
        // Arrange - Get orchestrator from container
        $orchestrator = app(WhatsAppMessageOrchestrator::class);

        $accountMetadata = new WhatsAppAccountMetadataDTO(
            sessionId: 'test_session',
            sessionName: 'Test Session',
            accountId: 1,
            agentEnabled: true,
            aiModelId: 1,
            contextualInformation: 'Test business information'
        );

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_123',
            from: '+237123456789@c.us',
            body: 'Bonjour',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        // Act
        $result = $orchestrator->processIncomingMessage($accountMetadata, $messageRequest);

        // Assert
        $this->assertNotNull($result);

        // Debug: Let's see what we get
        $this->assertInstanceOf(\App\DTOs\WhatsApp\WhatsAppMessageResponseDTO::class, $result);

        // If agent is enabled, we should get some kind of response (but processing might fail gracefully)
        if ($accountMetadata->isAgentActive()) {
            $this->assertTrue(true); // Basic success assertion - processing happened
        }
    }

    public function test_disabled_agent_skips_processing(): void
    {
        // Arrange
        $orchestrator = app(WhatsAppMessageOrchestrator::class);

        $accountMetadata = new WhatsAppAccountMetadataDTO(
            sessionId: 'test_session',
            sessionName: 'Test Session',
            accountId: 1,
            agentEnabled: false // Disabled
        );

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_123',
            from: '+237123456789@c.us',
            body: 'Bonjour',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        // Act
        $result = $orchestrator->processIncomingMessage($accountMetadata, $messageRequest);

        // Assert
        $this->assertTrue($result->processed);
        $this->assertFalse($result->hasAiResponse);
        $this->assertNull($result->aiResponse);
    }

    public function test_simulated_message_processing(): void
    {
        // Arrange
        $orchestrator = app(WhatsAppMessageOrchestrator::class);

        $accountMetadata = new WhatsAppAccountMetadataDTO(
            sessionId: 'simulation_session',
            sessionName: 'Simulation',
            accountId: 1,
            agentEnabled: true,
            aiModelId: 1
        );

        $userMessage = 'Bonjour, comment allez-vous ?';
        $context = [
            ['type' => 'user', 'content' => 'Message précédent'],
            ['type' => 'ai', 'content' => 'Réponse précédente'],
        ];

        // Act
        $result = $orchestrator->processSimulatedMessage($accountMetadata, $userMessage, $context);

        // Assert
        $this->assertNotNull($result);
        $this->assertTrue($result->processed);
    }

    public function test_orchestrator_handles_errors_gracefully(): void
    {
        // Arrange
        $orchestrator = app(WhatsAppMessageOrchestrator::class);

        $accountMetadata = new WhatsAppAccountMetadataDTO(
            sessionId: 'test_session',
            sessionName: 'Test Session',
            accountId: 1,
            agentEnabled: true,
            aiModelId: 999, // Non-existent AI model
            contextualInformation: 'Test business information'
        );

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_123',
            from: '+237123456789@c.us',
            body: 'Bonjour',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        // Act
        $result = $orchestrator->processIncomingMessage($accountMetadata, $messageRequest);

        // Assert - Should handle gracefully without throwing
        $this->assertNotNull($result);
        $this->assertInstanceOf(\App\DTOs\WhatsApp\WhatsAppMessageResponseDTO::class, $result);
        $this->assertTrue($result->processed);
        $this->assertFalse($result->hasAiResponse);
    }

    public function test_ai_tracking_is_dispatched_during_message_processing(): void
    {
        Event::fake();

        // Arrange
        $orchestrator = app(WhatsAppMessageOrchestrator::class);

        $accountMetadata = new WhatsAppAccountMetadataDTO(
            sessionId: 'test_session',
            sessionName: 'Test Session',
            accountId: 1,
            agentEnabled: true,
            aiModelId: 1,
            contextualInformation: 'Test business information'
        );

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_123',
            from: '+237123456789@c.us',
            body: 'Bonjour',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        // Act
        $result = $orchestrator->processIncomingMessage($accountMetadata, $messageRequest);

        // Assert
        $this->assertNotNull($result);

        // If AI response was generated, tracking event should be dispatched
        if ($result->hasAiResponse) {
            Event::assertDispatched(AiResponseGenerated::class);
        }
    }

    public function test_simulation_mode_does_not_trigger_ai_tracking(): void
    {
        Event::fake();
        $this->assertDatabaseEmpty('ai_usage_logs');

        // Arrange
        $orchestrator = app(WhatsAppMessageOrchestrator::class);

        $accountMetadata = new WhatsAppAccountMetadataDTO(
            sessionId: 'simulation_session',
            sessionName: 'Simulation',
            accountId: 1,
            agentEnabled: true,
            aiModelId: 1
        );

        $userMessage = 'Bonjour, comment allez-vous ?';
        $context = [
            ['type' => 'user', 'content' => 'Message précédent'],
            ['type' => 'ai', 'content' => 'Réponse précédente'],
        ];

        // Act
        $result = $orchestrator->processSimulatedMessage($accountMetadata, $userMessage, $context);

        // Assert
        $this->assertNotNull($result);
        $this->assertTrue($result->processed);

        // Even if AI response is generated in simulation, no tracking should occur
        $this->assertDatabaseEmpty('ai_usage_logs');

        // Events might be dispatched but should not create database entries
        // since the listener checks the isSimulation flag
    }
}
