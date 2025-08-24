<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Contracts\WhatsApp\AIProviderServiceInterface;
use App\DTOs\AI\AiRequestDTO;
use App\DTOs\WhatsApp\ProductDataDTO;
use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Models\AiModel;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\WhatsAppMessageOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WhatsAppMessageOrchestratorTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private WhatsAppMessageOrchestrator $orchestrator;
    private WhatsAppAccount $account;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = $this->createTestAccount();

        // Note: The orchestrator will be created after setting up the AI response mock
    }

    /** @test */
    public function it_processes_message_and_returns_products_as_dto_array(): void
    {
        // Arrange
        $products = $this->createTestProducts();
        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_123',
            from: '237690000000',
            body: 'Montrez-moi vos produits disponibles',
            timestamp: time(),
            type: 'text',
            isGroup: false,
        );

        // Setup AI response with products
        $this->setupAIResponse(
            message: 'Voici nos produits disponibles',
            action: 'show_products',
            productIds: $products->pluck('id')->toArray()
        );

        // Create orchestrator AFTER setting up the mock
        $this->orchestrator = $this->app->make(WhatsAppMessageOrchestrator::class);

        // Act
        $response = $this->orchestrator->processMessage(
            $this->account,
            $messageRequest,
            'Historique de conversation test'
        );

        // Debug: afficher la réponse pour comprendre l'erreur
        if (! $response->wasSuccessful()) {
            $this->fail('Response was not successful. Error: '.($response->processingError ?? 'Unknown error'));
        }

        // Assert
        $this->assertTrue($response->wasSuccessful());
        $this->assertTrue($response->hasAiResponse);
        $this->assertNotEmpty($response->products);

        // Vérification critique : chaque produit doit être un ProductDataDTO
        foreach ($response->products as $product) {
            $this->assertInstanceOf(ProductDataDTO::class, $product);
        }

        // Test de conversion webhook
        $webhookResponse = $response->toWebhookResponse();

        $this->assertTrue($webhookResponse['success']);
        $this->assertIsArray($webhookResponse['products']);

        // Chaque produit dans la réponse webhook doit être un array
        foreach ($webhookResponse['products'] as $productArray) {
            $this->assertIsArray($productArray);
            $this->assertArrayHasKey('formattedProductMessage', $productArray);
            $this->assertArrayHasKey('mediaUrls', $productArray);
            $this->assertIsString($productArray['formattedProductMessage']);
            $this->assertIsArray($productArray['mediaUrls']);
        }
    }

    /** @test */
    public function it_handles_empty_products_gracefully(): void
    {
        // Arrange
        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_456',
            from: '237690000000',
            body: 'Bonjour comment allez-vous?',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        // Setup AI response without products
        $this->setupAIResponse(
            message: 'Bonjour ! Comment puis-je vous aider ?',
            action: 'text',
            productIds: []
        );

        // Create orchestrator AFTER setting up the mock
        $this->orchestrator = $this->app->make(WhatsAppMessageOrchestrator::class);

        // Act
        $response = $this->orchestrator->processMessage(
            $this->account,
            $messageRequest,
            ''
        );

        // Assert
        $this->assertTrue($response->wasSuccessful());
        $this->assertEmpty($response->products);

        $webhookResponse = $response->toWebhookResponse();
        $this->assertEmpty($webhookResponse['products']);
    }

    /** @test */
    public function it_filters_inactive_products(): void
    {
        // Arrange
        $activeProduct = UserProduct::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $inactiveProduct = UserProduct::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => false,
        ]);

        $this->account->userProducts()->attach([
            $activeProduct->id,
            $inactiveProduct->id,
        ]);

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_789',
            from: '237690000000',
            body: 'Montrez-moi tous les produits',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        // Setup AI response with both IDs
        $this->setupAIResponse(
            message: 'Voici tous nos produits',
            action: 'show_products',
            productIds: [$activeProduct->id, $inactiveProduct->id]
        );

        // Create orchestrator AFTER setting up the mock
        $this->orchestrator = $this->app->make(WhatsAppMessageOrchestrator::class);

        // Act
        $response = $this->orchestrator->processMessage(
            $this->account,
            $messageRequest,
            ''
        );

        // Assert
        $this->assertCount(1, $response->products); // Seulement le produit actif
        $this->assertInstanceOf(ProductDataDTO::class, $response->products[0]);
        // On teste le contenu au lieu de l'ID
        $this->assertStringContainsString($activeProduct->title, $response->products[0]->formattedProductMessage);
    }

    private function createTestAccount(): WhatsAppAccount
    {
        $aiModel = AiModel::factory()->create([
            'model_identifier' => 'deepseek-chat',
        ]);

        return WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'session_id' => 'test_session_'.uniqid(),
            'ai_model_id' => $aiModel->id,
            'agent_prompt' => 'Tu es un assistant commercial.',
        ]);
    }

    private function createTestProducts(int $count = 3)
    {
        $products = UserProduct::factory()->count($count)->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        // Lier les produits au compte WhatsApp
        $this->account->userProducts()->attach($products->pluck('id'));

        return $products;
    }

    private function setupAIResponse(string $message = 'Voici nos produits disponibles', string $action = 'show_products', array $productIds = [1, 2, 3]): void
    {
        // Créer une réponse JSON structurée comme le parser l'attend
        $aiResponseJson = json_encode([
            'message' => $message,
            'action' => $action,
            'products' => $productIds,
        ], JSON_UNESCAPED_UNICODE);

        // Debug : afficher le JSON généré
        Log::info('[TEST] AI Response JSON:', ['json' => $aiResponseJson]);

        $aiResponse = new WhatsAppAIResponseDTO(
            response: $aiResponseJson,
            model: 'test-model',
            confidence: 0.9,
            tokensUsed: 10,
            cost: 0.01
        );

        // Create anonymous class for AIProviderServiceInterface
        $aiProviderService = new class($aiResponse) implements AIProviderServiceInterface
        {
            private WhatsAppAIResponseDTO $mockedAiResponse;

            public function __construct(WhatsAppAIResponseDTO $mockedAiResponse)
            {
                $this->mockedAiResponse = $mockedAiResponse;
            }

            public function generateResponse(AiRequestDTO $aiRequest): ?WhatsAppAIResponseDTO
            {
                return $this->mockedAiResponse;
            }

            public function canGenerateResponse(WhatsAppAccount $account): bool
            {
                return true;
            }

            public function getAvailableModels(WhatsAppAccount $account): array
            {
                return [];
            }

            public function getUsageStats(WhatsAppAccount $account): array
            {
                return [];
            }
        };

        // Bind the anonymous classes to the service container
        $this->app->instance(AIProviderServiceInterface::class, $aiProviderService);
    }
}
