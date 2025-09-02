<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\DTOs\AI\AiRequestDTO;
use App\DTOs\WhatsApp\ProductDataDTO;
use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Events\WhatsApp\AiResponseGenerated;
use App\Models\AiModel;
use App\Models\AiUsageLog;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\Contracts\AIProviderServiceInterface;
use App\Services\WhatsApp\WhatsAppMessageOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WhatsAppMessageOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_processes_message_and_returns_products_as_dto_array(): void
    {
        // Arrange - Create test data with explicit values
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        $aiModel = AiModel::factory()->create([
            'model_identifier' => 'test-gpt-4',
            'name' => 'Test GPT-4',
            'provider' => 'openai',
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_products_123',
            'ai_model_id' => $aiModel->id,
            'agent_prompt' => 'Tu es un assistant commercial pour les tests.',
            'agent_enabled' => true,
            'phone_number' => '237690000000',
        ]);

        $product1 = UserProduct::factory()->create([
            'user_id' => $user->id,
            'title' => 'iPhone 15 Pro',
            'description' => 'Latest iPhone model',
            'price' => 1200.00,
            'is_active' => true,
        ]);

        $product2 = UserProduct::factory()->create([
            'user_id' => $user->id,
            'title' => 'Samsung Galaxy S24',
            'description' => 'Latest Samsung model',
            'price' => 1000.00,
            'is_active' => true,
        ]);

        $account->userProducts()->attach([$product1->id, $product2->id]);

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_products_test_001',
            from: '237690000000',
            body: 'Montrez-moi vos produits disponibles',
            timestamp: 1703001600, // Fixed timestamp: 2023-12-19 12:00:00
            type: 'text',
            isGroup: false,
        );

        // Setup AI response with explicit product IDs
        $aiResponseJson = json_encode([
            'message' => 'Voici nos produits technologiques disponibles',
            'action' => 'show_products',
            'products' => [$product1->id, $product2->id],
        ], JSON_UNESCAPED_UNICODE);

        $aiResponse = new WhatsAppAIResponseDTO(
            response: $aiResponseJson,
            model: 'test-gpt-4',
            confidence: 0.95,
            tokensUsed: 150,
            cost: 0.025
        );

        $mockAiProviderService = new class($aiResponse) implements AIProviderServiceInterface
        {
            public function __construct(private WhatsAppAIResponseDTO $mockedResponse) {}

            public function generateResponse(AiRequestDTO $aiRequest): ?WhatsAppAIResponseDTO
            {
                return $this->mockedResponse;
            }

            public function canGenerateResponse(WhatsAppAccount $account): bool
            {
                return true;
            }

            public function getAvailableModels(WhatsAppAccount $account): array
            {
                return ['test-gpt-4'];
            }

            public function getUsageStats(WhatsAppAccount $account): array
            {
                return [];
            }
        };

        $this->app->instance(AIProviderServiceInterface::class, $mockAiProviderService);
        $orchestrator = $this->app->make(WhatsAppMessageOrchestrator::class);

        // Act
        $response = $orchestrator->processMessage(
            $account,
            $messageRequest,
            'Historique: Client demande des informations produits'
        );

        // Assert
        $this->assertTrue($response->wasSuccessful());
        $this->assertTrue($response->hasAiResponse);
        $this->assertEquals('Voici nos produits technologiques disponibles', $response->aiResponse);
        $this->assertCount(2, $response->products);

        // Verify each product is a ProductDataDTO with expected data
        $productDTOs = $response->products;
        $this->assertInstanceOf(ProductDataDTO::class, $productDTOs[0]);
        $this->assertInstanceOf(ProductDataDTO::class, $productDTOs[1]);

        // Verify product content matches our test data
        $productMessages = array_map(
            fn (ProductDataDTO $dto) => $dto->formattedProductMessage,
            $productDTOs
        );
        $this->assertStringContainsString('iPhone 15 Pro', implode(' ', $productMessages));
        $this->assertStringContainsString('Samsung Galaxy S24', implode(' ', $productMessages));

        // Test webhook response conversion
        $webhookResponse = $response->toWebhookResponse();
        $this->assertTrue($webhookResponse['success']);
        $this->assertIsArray($webhookResponse['products']);
        $this->assertCount(2, $webhookResponse['products']);

        foreach ($webhookResponse['products'] as $productArray) {
            $this->assertIsArray($productArray);
            $this->assertArrayHasKey('formattedProductMessage', $productArray);
            $this->assertArrayHasKey('mediaUrls', $productArray);
            $this->assertIsString($productArray['formattedProductMessage']);
            $this->assertIsArray($productArray['mediaUrls']);
        }
    }

    #[Test]
    public function it_handles_empty_products_gracefully(): void
    {
        // Arrange - Create test data with explicit values
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'UserEmpty',
            'email' => 'empty@example.com',
        ]);

        $aiModel = AiModel::factory()->create([
            'model_identifier' => 'test-claude-3',
            'name' => 'Test Claude 3',
            'provider' => 'anthropic',
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_empty_456',
            'ai_model_id' => $aiModel->id,
            'agent_prompt' => 'Tu es un assistant conversationnel.',
            'agent_enabled' => true,
            'phone_number' => '237690000001',
        ]);

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_empty_test_002',
            from: '237690000001',
            body: 'Bonjour comment allez-vous?',
            timestamp: 1703001700, // Fixed timestamp: 2023-12-19 12:01:40
            type: 'text',
            isGroup: false
        );

        // Setup AI response without products
        $aiResponseJson = json_encode([
            'message' => 'Bonjour ! Je vais très bien, merci. Comment puis-je vous aider aujourd\'hui ?',
            'action' => 'text',
            'products' => [],
        ], JSON_UNESCAPED_UNICODE);

        $aiResponse = new WhatsAppAIResponseDTO(
            response: $aiResponseJson,
            model: 'test-claude-3',
            confidence: 0.98,
            tokensUsed: 75,
            cost: 0.015
        );

        $mockAiProviderService = new class($aiResponse) implements AIProviderServiceInterface
        {
            public function __construct(private WhatsAppAIResponseDTO $mockedResponse) {}

            public function generateResponse(AiRequestDTO $aiRequest): ?WhatsAppAIResponseDTO
            {
                return $this->mockedResponse;
            }

            public function canGenerateResponse(WhatsAppAccount $account): bool
            {
                return true;
            }

            public function getAvailableModels(WhatsAppAccount $account): array
            {
                return ['test-claude-3'];
            }

            public function getUsageStats(WhatsAppAccount $account): array
            {
                return [];
            }
        };

        $this->app->instance(AIProviderServiceInterface::class, $mockAiProviderService);
        $orchestrator = $this->app->make(WhatsAppMessageOrchestrator::class);

        // Act
        $response = $orchestrator->processMessage(
            $account,
            $messageRequest,
            ''
        );

        // Assert
        $this->assertTrue($response->wasSuccessful());
        $this->assertTrue($response->hasAiResponse);
        $this->assertEquals('Bonjour ! Je vais très bien, merci. Comment puis-je vous aider aujourd\'hui ?', $response->aiResponse);
        $this->assertEmpty($response->products);
        $this->assertEquals(0, $response->getProductsCount());

        $webhookResponse = $response->toWebhookResponse();
        $this->assertTrue($webhookResponse['success']);
        $this->assertEmpty($webhookResponse['products']);
    }

    #[Test]
    public function it_filters_inactive_products(): void
    {
        // Arrange - Create test data with explicit values
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'UserFilter',
            'email' => 'filter@example.com',
        ]);

        $aiModel = AiModel::factory()->create([
            'model_identifier' => 'test-gpt-3.5',
            'name' => 'Test GPT-3.5',
            'provider' => 'openai',
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_filter_789',
            'ai_model_id' => $aiModel->id,
            'agent_prompt' => 'Tu es un assistant pour filtrer les produits.',
            'agent_enabled' => true,
            'phone_number' => '237690000002',
        ]);

        $activeProduct = UserProduct::factory()->create([
            'user_id' => $user->id,
            'title' => 'MacBook Pro 16" M3',
            'description' => 'Professional laptop',
            'price' => 2500.00,
            'is_active' => true,
        ]);

        $inactiveProduct = UserProduct::factory()->create([
            'user_id' => $user->id,
            'title' => 'Old MacBook Air',
            'description' => 'Discontinued model',
            'price' => 800.00,
            'is_active' => false,
        ]);

        $account->userProducts()->attach([$activeProduct->id, $inactiveProduct->id]);

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_filter_test_003',
            from: '237690000002',
            body: 'Montrez-moi tous les MacBook disponibles',
            timestamp: 1703001800, // Fixed timestamp: 2023-12-19 12:03:20
            type: 'text',
            isGroup: false
        );

        // Setup AI response with both active and inactive product IDs
        $aiResponseJson = json_encode([
            'message' => 'Voici tous nos MacBook en stock et hors stock',
            'action' => 'show_products',
            'products' => [$activeProduct->id, $inactiveProduct->id],
        ], JSON_UNESCAPED_UNICODE);

        $aiResponse = new WhatsAppAIResponseDTO(
            response: $aiResponseJson,
            model: 'test-gpt-3.5',
            confidence: 0.92,
            tokensUsed: 120,
            cost: 0.018
        );

        $mockAiProviderService = new class($aiResponse) implements AIProviderServiceInterface
        {
            public function __construct(private WhatsAppAIResponseDTO $mockedResponse) {}

            public function generateResponse(AiRequestDTO $aiRequest): ?WhatsAppAIResponseDTO
            {
                return $this->mockedResponse;
            }

            public function canGenerateResponse(WhatsAppAccount $account): bool
            {
                return true;
            }

            public function getAvailableModels(WhatsAppAccount $account): array
            {
                return ['test-gpt-3.5'];
            }

            public function getUsageStats(WhatsAppAccount $account): array
            {
                return [];
            }
        };

        $this->app->instance(AIProviderServiceInterface::class, $mockAiProviderService);
        $orchestrator = $this->app->make(WhatsAppMessageOrchestrator::class);

        // Act
        $response = $orchestrator->processMessage(
            $account,
            $messageRequest,
            'Historique: Client demande tous les MacBook'
        );

        // Assert - Only active product should be returned
        $this->assertTrue($response->wasSuccessful());
        $this->assertCount(1, $response->products, 'Only active products should be returned');
        $this->assertInstanceOf(ProductDataDTO::class, $response->products[0]);

        // Verify the returned product is the active one
        $returnedProduct = $response->products[0];
        $this->assertStringContainsString('MacBook Pro 16" M3', $returnedProduct->formattedProductMessage);
        $this->assertStringNotContainsString('Old MacBook Air', $returnedProduct->formattedProductMessage);
    }

    #[Test]
    public function it_dispatches_ai_tracking_event_when_not_in_simulation_mode(): void
    {
        Event::fake();

        // Arrange - Create test data with explicit values
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'UserEvents',
            'email' => 'events@example.com',
        ]);

        $aiModel = AiModel::factory()->create([
            'model_identifier' => 'test-deepseek',
            'name' => 'Test DeepSeek',
            'provider' => 'deepseek',
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_events_101',
            'ai_model_id' => $aiModel->id,
            'agent_prompt' => 'Tu es un assistant pour les événements de test.',
            'agent_enabled' => true,
            'phone_number' => '237690000003',
        ]);

        $product = UserProduct::factory()->create([
            'user_id' => $user->id,
            'title' => 'iPad Pro 12.9"',
            'description' => 'Professional tablet',
            'price' => 1100.00,
            'is_active' => true,
        ]);

        $account->userProducts()->attach([$product->id]);

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_events_test_004',
            from: '237690000003',
            body: 'Montrez-moi les tablettes disponibles',
            timestamp: 1703001900, // Fixed timestamp: 2023-12-19 12:05:00
            type: 'text',
            isGroup: false,
        );

        // Setup AI response with cost metadata for tracking
        $aiResponseJson = json_encode([
            'message' => 'Voici notre sélection de tablettes professionnelles',
            'action' => 'show_products',
            'products' => [$product->id],
        ], JSON_UNESCAPED_UNICODE);

        $aiResponse = new WhatsAppAIResponseDTO(
            response: $aiResponseJson,
            model: 'test-deepseek',
            confidence: 0.94,
            tokensUsed: 200,
            cost: 0.012,
            metadata: [
                'model' => 'test-deepseek',
                'provider' => 'deepseek',
                'usage' => [
                    'prompt_tokens' => 80,
                    'completion_tokens' => 120,
                    'total_tokens' => 200,
                    'prompt_cache_hit_tokens' => 0,
                ],
                'costs' => [
                    'prompt_cost_usd' => 0.004,
                    'completion_cost_usd' => 0.008,
                    'cached_cost_usd' => 0.000,
                    'total_cost_usd' => 0.012,
                    'total_cost_xaf' => 7.80,
                ],
                'attempts' => 1,
            ]
        );

        $mockAiProviderService = new class($aiResponse) implements AIProviderServiceInterface
        {
            public function __construct(private WhatsAppAIResponseDTO $mockedResponse) {}

            public function generateResponse(AiRequestDTO $aiRequest): ?WhatsAppAIResponseDTO
            {
                return $this->mockedResponse;
            }

            public function canGenerateResponse(WhatsAppAccount $account): bool
            {
                return true;
            }

            public function getAvailableModels(WhatsAppAccount $account): array
            {
                return ['test-deepseek'];
            }

            public function getUsageStats(WhatsAppAccount $account): array
            {
                return [];
            }
        };

        $this->app->instance(AIProviderServiceInterface::class, $mockAiProviderService);
        $orchestrator = $this->app->make(WhatsAppMessageOrchestrator::class);

        // Act - Process message NOT in simulation mode
        $response = $orchestrator->processMessage(
            $account,
            $messageRequest,
            'Historique: Client demande des tablettes'
        );

        // Assert
        $this->assertTrue($response->wasSuccessful());

        // Verify AI tracking event was dispatched
        Event::assertDispatched(AiResponseGenerated::class, function ($event) use ($account, $messageRequest) {
            return $event->account->id === $account->id
                && $event->messageRequest->id === $messageRequest->id
                && $event->requestBody === 'Montrez-moi les tablettes disponibles'
                && $event->aiResponse->model === 'test-deepseek';
        });
    }

    #[Test]
    public function it_does_not_track_ai_usage_in_simulation_mode(): void
    {
        AiUsageLog::truncate();
        $this->assertDatabaseEmpty('ai_usage_logs');

        // Arrange - Create test data with explicit values
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'UserSimulation',
            'email' => 'simulation@example.com',
        ]);

        $aiModel = AiModel::factory()->create([
            'model_identifier' => 'test-llama-3',
            'name' => 'Test Llama 3',
            'provider' => 'meta',
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_simulation_202',
            'ai_model_id' => $aiModel->id,
            'agent_prompt' => 'Tu es un assistant en mode simulation.',
            'agent_enabled' => true,
            'phone_number' => '237690000004',
        ]);

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_simulation_test_005',
            from: '237690000004',
            body: 'Test en mode simulation',
            timestamp: 1703002000, // Fixed timestamp: 2023-12-19 12:06:40
            type: 'text',
            isGroup: false
        );

        // Setup AI response with cost metadata
        $aiResponseJson = json_encode([
            'message' => 'Ceci est une réponse de test en mode simulation',
            'action' => 'text',
            'products' => [],
        ], JSON_UNESCAPED_UNICODE);

        $aiResponse = new WhatsAppAIResponseDTO(
            response: $aiResponseJson,
            model: 'test-llama-3',
            confidence: 0.89,
            tokensUsed: 50,
            cost: 0.005,
            metadata: [
                'model' => 'test-llama-3',
                'provider' => 'meta',
                'usage' => [
                    'prompt_tokens' => 25,
                    'completion_tokens' => 25,
                    'total_tokens' => 50,
                    'prompt_cache_hit_tokens' => 0,
                ],
                'costs' => [
                    'prompt_cost_usd' => 0.002,
                    'completion_cost_usd' => 0.003,
                    'cached_cost_usd' => 0.000,
                    'total_cost_usd' => 0.005,
                    'total_cost_xaf' => 3.25,
                ],
                'attempts' => 1,
            ]
        );

        $mockAiProviderService = new class($aiResponse) implements AIProviderServiceInterface
        {
            public function __construct(private WhatsAppAIResponseDTO $mockedResponse) {}

            public function generateResponse(AiRequestDTO $aiRequest): ?WhatsAppAIResponseDTO
            {
                return $this->mockedResponse;
            }

            public function canGenerateResponse(WhatsAppAccount $account): bool
            {
                return true;
            }

            public function getAvailableModels(WhatsAppAccount $account): array
            {
                return ['test-llama-3'];
            }

            public function getUsageStats(WhatsAppAccount $account): array
            {
                return [];
            }
        };

        $this->app->instance(AIProviderServiceInterface::class, $mockAiProviderService);
        $orchestrator = $this->app->make(WhatsAppMessageOrchestrator::class);

        // Act - Process message IN simulation mode
        $response = $orchestrator->processMessage(
            $account,
            $messageRequest,
            'Historique: Test simulation',
            isSimulation: true
        );

        // Assert
        $this->assertTrue($response->wasSuccessful());
        $this->assertEquals('Ceci est une réponse de test en mode simulation', $response->aiResponse);

        // Verify no AI usage was tracked in simulation mode
        $this->assertDatabaseEmpty('ai_usage_logs');
    }

    #[Test]
    public function it_tracks_ai_usage_with_correct_cost_data(): void
    {
        AiUsageLog::truncate();
        $this->assertDatabaseEmpty('ai_usage_logs');

        // Arrange - Create test data with explicit values
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'UserCostTracking',
            'email' => 'cost-tracking@example.com',
        ]);

        $aiModel = AiModel::factory()->create([
            'model_identifier' => 'test-gpt-4-turbo',
            'name' => 'Test GPT-4 Turbo',
            'provider' => 'openai',
        ]);

        $account = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_cost_303',
            'ai_model_id' => $aiModel->id,
            'agent_prompt' => 'Tu es un assistant pour le suivi des coûts.',
            'agent_enabled' => true,
            'phone_number' => '237690000005',
        ]);

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_cost_tracking_006',
            from: '237690000005',
            body: 'Analysez mes données de coût IA',
            timestamp: 1703002100, // Fixed timestamp: 2023-12-19 12:08:20
            type: 'text',
            isGroup: false
        );

        // Setup AI response with specific cost data for verification
        $expectedTotalCostUsd = 0.0345;
        $expectedTotalCostXaf = 22.47;
        $expectedTokensUsed = 450;

        $aiResponseJson = json_encode([
            'message' => 'Voici l\'analyse détaillée de vos coûts d\'utilisation de l\'IA',
            'action' => 'text',
            'products' => [],
        ], JSON_UNESCAPED_UNICODE);

        $aiResponse = new WhatsAppAIResponseDTO(
            response: $aiResponseJson,
            model: 'test-gpt-4-turbo',
            confidence: 0.97,
            tokensUsed: $expectedTokensUsed,
            cost: $expectedTotalCostUsd,
            metadata: [
                'model' => 'test-gpt-4-turbo',
                'provider' => 'openai',
                'usage' => [
                    'prompt_tokens' => 200,
                    'completion_tokens' => 250,
                    'total_tokens' => $expectedTokensUsed,
                    'prompt_cache_hit_tokens' => 0,
                ],
                'costs' => [
                    'prompt_cost_usd' => 0.0120,
                    'completion_cost_usd' => 0.0225,
                    'cached_cost_usd' => 0.0000,
                    'total_cost_usd' => $expectedTotalCostUsd,
                    'total_cost_xaf' => $expectedTotalCostXaf,
                ],
                'attempts' => 1,
            ]
        );

        $mockAiProviderService = new class($aiResponse) implements AIProviderServiceInterface
        {
            public function __construct(private WhatsAppAIResponseDTO $mockedResponse) {}

            public function generateResponse(AiRequestDTO $aiRequest): ?WhatsAppAIResponseDTO
            {
                return $this->mockedResponse;
            }

            public function canGenerateResponse(WhatsAppAccount $account): bool
            {
                return true;
            }

            public function getAvailableModels(WhatsAppAccount $account): array
            {
                return ['test-gpt-4-turbo'];
            }

            public function getUsageStats(WhatsAppAccount $account): array
            {
                return [];
            }
        };

        $this->app->instance(AIProviderServiceInterface::class, $mockAiProviderService);
        $orchestrator = $this->app->make(WhatsAppMessageOrchestrator::class);

        // Act - Process message (not in simulation)
        $response = $orchestrator->processMessage(
            $account,
            $messageRequest,
            'Historique: Demande d\'analyse des coûts'
        );

        // Assert
        $this->assertTrue($response->wasSuccessful());
        $this->assertEquals('Voici l\'analyse détaillée de vos coûts d\'utilisation de l\'IA', $response->aiResponse);

        // Verify AI usage is tracked with correct cost data
        $usageLogs = AiUsageLog::where('whatsapp_account_id', $account->id)->get();
        $this->assertGreaterThan(0, $usageLogs->count(), 'At least one AI usage log should be created');

        $usageLog = $usageLogs->first();
        $this->assertEquals($user->id, $usageLog->user_id);
        $this->assertEquals($account->id, $usageLog->whatsapp_account_id);
        $this->assertEquals($expectedTokensUsed, $usageLog->total_tokens);
        $this->assertEquals($expectedTotalCostUsd, (float) $usageLog->total_cost_usd);
        $this->assertEquals($expectedTotalCostXaf, (float) $usageLog->total_cost_xaf);
        $this->assertEquals('test-gpt-4-turbo', $usageLog->ai_model);
        $this->assertEquals('openai', $usageLog->provider);
        $this->assertEquals(200, $usageLog->prompt_tokens);
        $this->assertEquals(250, $usageLog->completion_tokens);
        $this->assertEquals(strlen('Analysez mes données de coût IA'), $usageLog->request_length);
    }
}
