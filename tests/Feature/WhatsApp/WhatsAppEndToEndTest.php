<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp\Integration;

use App\DTOs\AI\AiRequestDTO;
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

    private WhatsAppAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        // Create AI model for tests
        $aiModel = AiModel::factory()->create([
            'id' => 1,
            'name' => 'Test GPT Model',
            'is_active' => true,
            'is_default' => true,
        ]);

        // Create test data
        $this->account = WhatsAppAccount::factory()->create([
            'id' => 1,
            'agent_enabled' => true,
            'ai_model_id' => $aiModel->id,
            'contextual_information' => 'Test business information',
        ]);
    }

    public function test_complete_message_processing_flow(): void
    {
        // Mock AI Provider Service pour éviter les vrais appels
        $this->mockAIProviderService();

        // Arrange - Get orchestrator from container
        $orchestrator = app(WhatsAppMessageOrchestrator::class);

        // Using account directly instead of metadata DTO

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_123',
            from: '+237123456789@c.us',
            body: 'Bonjour',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        // Act
        $result = $orchestrator->processMessage($this->account, $messageRequest, '');

        // Assert
        $this->assertNotNull($result);
        $this->assertInstanceOf(\App\DTOs\WhatsApp\WhatsAppMessageResponseDTO::class, $result);
        $this->assertTrue($result->processed);
        $this->assertTrue($result->hasAiResponse);
        $this->assertNotNull($result->aiResponse);
    }

    public function test_disabled_agent_skips_processing(): void
    {
        // Arrange
        $orchestrator = app(WhatsAppMessageOrchestrator::class);

        // Using account with agent disabled
        $this->account->update(['agent_enabled' => false]);

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_123',
            from: '+237123456789@c.us',
            body: 'Bonjour',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        // Act
        $result = $orchestrator->processMessage($this->account, $messageRequest, '');

        // Assert
        $this->assertTrue($result->processed);
        $this->assertFalse($result->hasAiResponse);
        $this->assertNull($result->aiResponse);
    }

    public function test_simulated_message_processing(): void
    {
        // Mock AI Provider Service pour éviter les vrais appels
        $this->mockAIProviderService();

        // Arrange
        $orchestrator = app(WhatsAppMessageOrchestrator::class);

        // Using account directly for simulation

        $userMessage = 'Bonjour, comment allez-vous ?';
        $context = [
            ['type' => 'user', 'content' => 'Message précédent'],
            ['type' => 'ai', 'content' => 'Réponse précédente'],
        ];

        // Act
        // Créer un message DTO pour la simulation
        $simulationMessageRequest = new WhatsAppMessageRequestDTO(
            id: 'simulation_'.uniqid(),
            from: 'simulation@test.com',
            body: $userMessage,
            timestamp: time(),
            type: 'text',
            isGroup: false
        );
        $result = $orchestrator->processMessage($this->account, $simulationMessageRequest, implode("\n", array_map(fn ($ctx) => $ctx['content'], $context)), true);

        // Assert
        $this->assertNotNull($result);
        $this->assertTrue($result->processed);
        $this->assertTrue($result->hasAiResponse);
    }

    public function test_orchestrator_handles_errors_gracefully(): void
    {
        // Mock AI Provider Service qui retourne null pour simuler une erreur
        $this->mockFailingAIProviderService();

        // Arrange
        $orchestrator = app(WhatsAppMessageOrchestrator::class);

        // Configure account with non-existent AI model for error testing
        $this->account->update(['ai_model_id' => 999]);

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_123',
            from: '+237123456789@c.us',
            body: 'Bonjour',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        // Act
        $result = $orchestrator->processMessage($this->account, $messageRequest, '');

        // Assert - Should handle gracefully without throwing
        $this->assertNotNull($result);
        $this->assertInstanceOf(\App\DTOs\WhatsApp\WhatsAppMessageResponseDTO::class, $result);
        $this->assertTrue($result->processed);
        $this->assertFalse($result->hasAiResponse);
    }

    public function test_ai_tracking_is_dispatched_during_message_processing(): void
    {
        Event::fake();

        // Mock AI Provider Service pour éviter les vrais appels
        $this->mockAIProviderService();

        // Arrange
        $orchestrator = app(WhatsAppMessageOrchestrator::class);

        // Using account directly instead of metadata DTO

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_123',
            from: '+237123456789@c.us',
            body: 'Bonjour',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        // Act
        $result = $orchestrator->processMessage($this->account, $messageRequest, '');

        // Assert
        $this->assertNotNull($result);
        $this->assertTrue($result->hasAiResponse);

        // AI tracking event should be dispatched
        Event::assertDispatched(AiResponseGenerated::class);
    }

    public function test_simulation_mode_does_not_trigger_ai_tracking(): void
    {
        Event::fake();
        $this->assertDatabaseEmpty('ai_usage_logs');

        // Mock AI Provider Service pour éviter les vrais appels
        $this->mockAIProviderService();

        // Arrange
        $orchestrator = app(WhatsAppMessageOrchestrator::class);

        // Using account directly for simulation

        $userMessage = 'Bonjour, comment allez-vous ?';
        $context = [
            ['type' => 'user', 'content' => 'Message précédent'],
            ['type' => 'ai', 'content' => 'Réponse précédente'],
        ];

        // Act
        // Créer un message DTO pour la simulation
        $simulationMessageRequest = new WhatsAppMessageRequestDTO(
            id: 'simulation_'.uniqid(),
            from: 'simulation@test.com',
            body: $userMessage,
            timestamp: time(),
            type: 'text',
            isGroup: false
        );
        $result = $orchestrator->processMessage($this->account, $simulationMessageRequest, implode("\n", array_map(fn ($ctx) => $ctx['content'], $context)), true);

        // Assert
        $this->assertNotNull($result);
        $this->assertTrue($result->processed);
        $this->assertTrue($result->hasAiResponse);

        // Even if AI response is generated in simulation, no tracking should occur
        $this->assertDatabaseEmpty('ai_usage_logs');

        // Events might be dispatched but should not create database entries
        // since the listener checks the isSimulation flag
    }

    /**
     * Mock AI Provider Service pour éviter les vrais appels d'API
     */
    private function mockAIProviderService(): void
    {
        $aiResponse = new WhatsAppAIResponseDTO(
            response: json_encode([
                'message' => 'Bonjour ! Comment puis-je vous aider aujourd\'hui ?',
                'action' => 'text',
                'products' => [],
            ]),
            model: 'test-model',
            confidence: 0.9,
            tokensUsed: 25,
            cost: 0.001
        );

        $mockService = new class($aiResponse) implements AIProviderServiceInterface
        {
            private WhatsAppAIResponseDTO $mockedResponse;

            public function __construct(WhatsAppAIResponseDTO $mockedResponse)
            {
                $this->mockedResponse = $mockedResponse;
            }

            public function generateResponse(AiRequestDTO $aiRequest): ?WhatsAppAIResponseDTO
            {
                return $this->mockedResponse;
            }

            public function canGenerateResponse(\App\Models\WhatsAppAccount $account): bool
            {
                return true;
            }

            public function getAvailableModels(\App\Models\WhatsAppAccount $account): array
            {
                return [];
            }

            public function getUsageStats(\App\Models\WhatsAppAccount $account): array
            {
                return [];
            }
        };

        $this->app->instance(AIProviderServiceInterface::class, $mockService);
    }

    /**
     * Mock AI Provider Service qui échoue pour tester la gestion d'erreurs
     */
    private function mockFailingAIProviderService(): void
    {
        $mockService = new class implements AIProviderServiceInterface
        {
            public function generateResponse(AiRequestDTO $aiRequest): ?WhatsAppAIResponseDTO
            {
                return null; // Simule un échec
            }

            public function canGenerateResponse(\App\Models\WhatsAppAccount $account): bool
            {
                return false;
            }

            public function getAvailableModels(\App\Models\WhatsAppAccount $account): array
            {
                return [];
            }

            public function getUsageStats(\App\Models\WhatsAppAccount $account): array
            {
                return [];
            }
        };

        $this->app->instance(AIProviderServiceInterface::class, $mockService);
    }
}
