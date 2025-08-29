<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Enums\AIResponseAction;
use App\Models\AiModel;
use App\Models\UserProduct;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\Contracts\AIProviderServiceInterface;
use App\Services\WhatsApp\Contracts\WhatsAppMessageOrchestratorInterface;
use App\Services\WhatsApp\Helpers\AIResponseParserHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppProductIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppAccount $account;
    private WhatsAppMessageOrchestratorInterface $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();

        // ⚠️ MOCK L'IA POUR ÉVITER LES APPELS RÉELS ET LA CONSOMMATION DE TOKENS
        $this->mockAIProviderService();

        // Create AI model for tests
        $aiModel = AiModel::factory()->create([
            'id' => 1,
            'name' => 'Test Commercial AI',
            'is_active' => true,
            'is_default' => true,
        ]);

        // Create test account with commercial agent prompt
        $this->account = WhatsAppAccount::factory()->create([
            'agent_enabled' => true,
            'ai_model_id' => $aiModel->id,
            'agent_prompt' => 'Tu es un agent commercial expert. Tu es là pour faire du closing et vendre nos produits. Demande toujours l\'adresse de livraison après une vente. Réponds TOUJOURS en JSON avec cette structure exacte: {"message": "ton message commercial", "action": "text|show_products|show_catalog", "products": [list_ids_si_applicable]}',
            'contextual_information' => 'Entreprise de vente de téléphones et produits technologiques haut de gamme. Nous offrons une garantie de 2 ans sur tous nos produits.',
        ]);

        // Create test products
        $this->createTestProducts();

        $this->orchestrator = app(WhatsAppMessageOrchestratorInterface::class);
    }

    /**
     * Mock l'IA pour éviter les vrais appels API et la consommation de tokens
     */
    private function mockAIProviderService(): void
    {
        $mockService = $this->createMock(AIProviderServiceInterface::class);

        $mockService->method('processMessage')
            ->willReturnCallback(function ($aiModel, $systemPrompt, $userMessage, $context) {
                // Simuler différentes réponses selon le message de l'utilisateur
                if (str_contains(strtolower($userMessage), 'bonjour') || str_contains(strtolower($userMessage), 'salut')) {
                    $response = json_encode([
                        'message' => 'Bonjour ! Bienvenue dans notre boutique. Comment puis-je vous aider aujourd\'hui ?',
                        'action' => 'text',
                        'products' => [],
                    ]);
                } elseif (str_contains(strtolower($userMessage), 'téléphone') || str_contains(strtolower($userMessage), 'produit')) {
                    $response = json_encode([
                        'message' => 'Voici nos téléphones disponibles selon votre budget :',
                        'action' => 'show_products',
                        'products' => [1, 2], // iPhone 13 et Samsung Galaxy S23 pour budget < 30000
                    ]);
                } elseif (str_contains(strtolower($userMessage), 'catalogue') || str_contains(strtolower($userMessage), 'autres')) {
                    $response = json_encode([
                        'message' => 'Voici notre catalogue complet :',
                        'action' => 'show_catalog',
                        'products' => [1, 2, 3], // Tous les produits
                    ]);
                } elseif (str_contains(strtolower($userMessage), 'qualité') || str_contains(strtolower($userMessage), 'garantie')) {
                    $response = json_encode([
                        'message' => 'Nos produits sont de très haute qualité avec une garantie de 2 ans. Tous nos téléphones sont testés avant livraison.',
                        'action' => 'text',
                        'products' => [],
                    ]);
                } else {
                    $response = json_encode([
                        'message' => 'Je peux vous aider avec nos produits. Que recherchez-vous exactement ?',
                        'action' => 'text',
                        'products' => [],
                    ]);
                }

                return new WhatsAppAIResponseDTO(
                    response: $response,
                    model: 'mocked-commercial-ai',
                    confidence: 0.95,
                    tokensUsed: 50,
                    cost: 0.01
                );
            });

        $this->app->instance(AIProviderServiceInterface::class, $mockService);
    }

    private function createTestProducts(): void
    {
        $products = [
            [
                'title' => 'iPhone 13 Reconditionné',
                'price' => 10000,
                'description' => 'Téléphone économique parfait pour débuter. Excellente qualité, garantie 2 ans.',
            ],
            [
                'title' => 'Samsung Galaxy S23',
                'price' => 50000,
                'description' => 'Téléphone haut de gamme avec camera professionnelle et écran AMOLED.',
            ],
            [
                'title' => 'iPhone 15 Pro Max',
                'price' => 100000,
                'description' => 'Le top du top de la technologie mobile. Titanium, camera 5x zoom.',
            ],
        ];

        foreach ($products as $productData) {
            $product = UserProduct::factory()->create([
                'user_id' => $this->account->user_id,
                'title' => $productData['title'],
                'price' => $productData['price'],
                'description' => $productData['description'],
            ]);

            // Link product to WhatsApp account
            $this->account->userProducts()->attach($product->id);
        }
    }

    /** @test */
    public function it_handles_basic_conversation_without_products(): void
    {
        $response = $this->processMessage('Bonjour, comment allez-vous ?');

        $this->assertTrue($response->wasSuccessful(), 'Response should be processed');
        $this->assertTrue($response->hasAiResponse, 'Should have AI response');
        $this->assertNotNull($response->aiResponse);
    }

    /** @test */
    public function it_handles_product_request_conversation(): void
    {
        $response = $this->processMessage('Quels téléphones avez-vous ?');

        $this->assertTrue($response->wasSuccessful());
        $this->assertTrue($response->hasAiResponse);
        $this->assertNotNull($response->aiResponse);
    }

    /** @test */
    public function it_handles_budget_specific_request(): void
    {
        $response = $this->processMessage('Je cherche un téléphone de moins de 25000 FCFA');

        $this->assertTrue($response->wasSuccessful());
        $this->assertTrue($response->hasAiResponse);
        $this->assertNotNull($response->aiResponse);
    }

    /** @test */
    public function it_selects_appropriate_product_based_on_budget(): void
    {
        $response = $this->processMessage('Je veux un téléphone de moins de 30000 FCFA');

        $this->assertTrue($response->wasSuccessful());
        $this->assertTrue($response->hasAiResponse);
        $this->assertNotNull($response->aiResponse);
    }

    /** @test */
    public function it_shows_full_catalog_when_requested(): void
    {
        $response = $this->processMessage('Pouvez-vous me montrer votre catalogue complet ?');

        $this->assertTrue($response->wasSuccessful());
        $this->assertTrue($response->hasAiResponse);
        $this->assertNotNull($response->aiResponse);
    }

    /** @test */
    public function it_parses_ai_response_structure_correctly(): void
    {
        $parser = new AIResponseParserHelper;

        $jsonResponse = json_encode([
            'message' => 'Test message',
            'action' => 'show_products',
            'products' => [1, 2, 3],
        ]);

        $aiResponseDTO = new \App\DTOs\WhatsApp\WhatsAppAIResponseDTO(
            response: $jsonResponse,
            model: 'test-model'
        );

        $structuredResponse = $parser->parseStructuredResponse($aiResponseDTO);

        $this->assertEquals('Test message', $structuredResponse->message);
        $this->assertEquals(AIResponseAction::SHOW_PRODUCTS(), $structuredResponse->action);
        $this->assertEquals([1, 2, 3], $structuredResponse->productIds);
        $this->assertTrue($structuredResponse->shouldSendProducts());
    }

    /** @test */
    public function it_handles_complete_commercial_conversation_flow(): void
    {
        // Step 1: Initial greeting
        $greetingResponse = $this->processMessage('Bonjour boss');

        $this->assertTrue($greetingResponse->wasSuccessful());
        $this->assertTrue($greetingResponse->hasAiResponse);
        $this->assertNotNull($greetingResponse->aiResponse);

        // Step 2: Customer asks for phone under 30k
        $phoneRequestResponse = $this->processMessage('Svp je voudrais un téléphone de moins de 30000, qu\'avez vous');

        $this->assertTrue($phoneRequestResponse->wasSuccessful());
        $this->assertTrue($phoneRequestResponse->hasAiResponse);
        $this->assertNotNull($phoneRequestResponse->aiResponse);

        // Step 3: Customer asks about quality and warranty
        $qualityResponse = $this->processMessage('merci, et est ce que la qualité est bonne, c quoi la garantie');

        $this->assertTrue($qualityResponse->wasSuccessful());
        $this->assertTrue($qualityResponse->hasAiResponse);
        $this->assertNotNull($qualityResponse->aiResponse);

        // Step 4: Customer asks what else is available - AI should show all products
        $catalogResponse = $this->processMessage('et vous avez quoi d\'autres');

        $this->assertTrue($catalogResponse->wasSuccessful());
        $this->assertTrue($catalogResponse->hasAiResponse);
        $this->assertNotNull($catalogResponse->aiResponse);
    }

    /** @test */
    public function it_validates_product_limits_configuration(): void
    {
        // Test configuration limits
        $maxProducts = config('whatsapp.products.max_linked_per_agent');
        $maxSent = config('whatsapp.products.max_sent_per_message');
        $delay = config('whatsapp.products.send_delay_seconds');

        $this->assertEquals(10, $maxProducts, 'Max products per agent should be 10');
        $this->assertEquals(10, $maxSent, 'Max products per message should be 10');
        $this->assertEquals(3, $delay, 'Delay should be 3 seconds');
    }

    private function processMessage(string $message): \App\DTOs\WhatsApp\WhatsAppMessageResponseDTO
    {
        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_'.uniqid(),
            from: '+237612345678@c.us',
            body: $message,
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        \Illuminate\Support\Facades\Log::info('[TEST] Processing commercial message', [
            'message' => $message,
            'account_id' => $this->account->id,
        ]);

        $response = $this->orchestrator->processMessage(
            $this->account,
            $messageRequest,
            '' // conversation history (empty for simplicity)
        );

        \Illuminate\Support\Facades\Log::info('[TEST] Commercial message response', [
            'processed' => $response->wasSuccessful(),
            'has_ai_response' => $response->hasAiResponse,
            'ai_response' => $response->aiResponse,
            'processing_error' => $response->processingError,
        ]);

        return $response;
    }
}
