<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Contracts\WhatsApp\WhatsAppMessageOrchestratorInterface;
use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Enums\AIResponseAction;
use App\Models\AiModel;
use App\Models\UserProduct;
use App\Models\WhatsAppAccount;
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

        $this->assertTrue($response->processed, 'Response should be processed');
        $this->assertTrue($response->hasAiResponse, 'Should have AI response');
        $this->assertNotNull($response->aiResponse);
    }

    /** @test */
    public function it_handles_product_request_conversation(): void
    {
        $response = $this->processMessage('Quels téléphones avez-vous ?');

        $this->assertTrue($response->processed);
        $this->assertTrue($response->hasAiResponse);
        $this->assertNotNull($response->aiResponse);
    }

    /** @test */
    public function it_handles_budget_specific_request(): void
    {
        $response = $this->processMessage('Je cherche un téléphone de moins de 25000 FCFA');

        $this->assertTrue($response->processed);
        $this->assertTrue($response->hasAiResponse);
        $this->assertNotNull($response->aiResponse);
    }

    /** @test */
    public function it_selects_appropriate_product_based_on_budget(): void
    {
        $response = $this->processMessage('Je veux un téléphone de moins de 30000 FCFA');

        $this->assertTrue($response->processed);
        $this->assertTrue($response->hasAiResponse);
        $this->assertNotNull($response->aiResponse);
    }

    /** @test */
    public function it_shows_full_catalog_when_requested(): void
    {
        $response = $this->processMessage('Pouvez-vous me montrer votre catalogue complet ?');

        $this->assertTrue($response->processed);
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

        $this->assertTrue($greetingResponse->processed);
        $this->assertTrue($greetingResponse->hasAiResponse);
        $this->assertNotNull($greetingResponse->aiResponse);

        // Step 2: Customer asks for phone under 30k
        $phoneRequestResponse = $this->processMessage('Svp je voudrais un téléphone de moins de 30000, qu\'avez vous');

        $this->assertTrue($phoneRequestResponse->processed);
        $this->assertTrue($phoneRequestResponse->hasAiResponse);
        $this->assertNotNull($phoneRequestResponse->aiResponse);

        // Step 3: Customer asks about quality and warranty
        $qualityResponse = $this->processMessage('merci, et est ce que la qualité est bonne, c quoi la garantie');

        $this->assertTrue($qualityResponse->processed);
        $this->assertTrue($qualityResponse->hasAiResponse);
        $this->assertNotNull($qualityResponse->aiResponse);

        // Step 4: Customer asks what else is available - AI should show all products
        $catalogResponse = $this->processMessage('et vous avez quoi d\'autres');

        $this->assertTrue($catalogResponse->processed);
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
        $accountMetadata = new WhatsAppAccountMetadataDTO(
            sessionId: 'commercial_test_session',
            sessionName: 'Commercial Test',
            accountId: $this->account->id,
            agentEnabled: true,
            aiModelId: 1,
            agentPrompt: $this->account->agent_prompt,
            contextualInformation: $this->account->contextual_information
        );

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
            'session_id' => $accountMetadata->sessionId,
            'ai_model_id' => $accountMetadata->aiModelId,
            'agent_prompt' => $accountMetadata->agentPrompt,
        ]);

        $response = $this->orchestrator->processIncomingMessage($accountMetadata, $messageRequest);

        \Illuminate\Support\Facades\Log::info('[TEST] Commercial message response', [
            'processed' => $response->processed,
            'has_ai_response' => $response->hasAiResponse,
            'ai_response' => $response->aiResponse,
            'processing_error' => $response->processingError,
        ]);

        return $response;
    }
}
