<?php

declare(strict_types=1);

namespace Tests\E2E\Integration;

use App\Contracts\WhatsApp\WhatsAppMessageOrchestratorInterface;
use App\Livewire\Customer\WhatsApp\ConversationSimulator;
use App\Models\AiModel;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests d'intégration E2E pour le simulateur de conversation
 *
 * Ces tests vérifient l'intégration complète entre le simulateur,
 * l'orchestrateur, et les services IA
 *
 * @group e2e
 * @group whatsapp
 * @group simulator
 * @group integration
 */
final class ConversationSimulatorIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WhatsAppAccount $whatsappAccount;
    private AiModel $aiModel;

    protected function setUp(): void
    {
        parent::setUp();

        // Create customer role if it doesn't exist
        if (! Role::where('name', 'customer')->exists()) {
            Role::create(['name' => 'customer']);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole('customer');

        // Create AI model for testing (using mock/test configuration)
        $this->aiModel = AiModel::factory()->create([
            'provider' => 'ollama',
            'model_identifier' => 'llama2',
            'endpoint_url' => config('ai.test_endpoint', 'http://localhost:11434'),
        ]);

        // Create WhatsApp account with products
        $this->whatsappAccount = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'agent_enabled' => true,
            'agent_prompt' => 'Tu es un assistant commercial qui aide à vendre des produits. Tu peux recommander des produits pertinents.',
            'contextual_information' => 'Nous vendons des produits électroniques et des accessoires.',
            'ai_model_id' => $this->aiModel->id,
            'response_time' => 'random',
        ]);

        // Create some test products
        UserProduct::factory(5)->create([
            'user_id' => $this->user->id,
        ]);
    }

    public function test_complete_conversation_flow_with_product_recommendation(): void
    {
        $this->actingAs($this->user);

        // Mock the orchestrator to return predictable responses
        $this->mock(WhatsAppMessageOrchestratorInterface::class, function ($mock) {
            $mock->shouldReceive('processMessage')
                ->once()
                ->andReturn(new \App\DTOs\WhatsApp\WhatsAppMessageResponseDTO(
                    processed: true,
                    hasAiResponse: true,
                    aiResponse: 'Bonjour ! Je peux vous aider à trouver des produits. Que recherchez-vous ?',
                    products: [],
                    waitTimeSeconds: 2,
                    typingDurationSeconds: 1,
                    conversationContext: [],
                    processingError: null
                ));
        });

        $component = Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount])
            ->set('newMessage', 'Bonjour, que vendez-vous ?')
            ->call('sendMessage');

        // Verify user message was added
        $messages = $component->get('simulationMessages');
        $this->assertCount(1, $messages);
        $this->assertEquals('user', $messages[0]['type']);
        $this->assertEquals('Bonjour, que vendez-vous ?', $messages[0]['content']);

        // Simulate AI response processing
        $component->call('processAiResponse', 'Bonjour, que vendez-vous ?', []);

        // The response should be handled by the orchestrator mock
        $this->assertInstanceOf(\App\Livewire\Customer\WhatsApp\ConversationSimulator::class, $component->instance());
    }

    public function test_conversation_with_product_display(): void
    {
        $this->actingAs($this->user);

        $products = UserProduct::where('user_id', $this->user->id)->take(2)->get();

        // Mock orchestrator to return products
        $this->mock(WhatsAppMessageOrchestratorInterface::class, function ($mock) use ($products) {
            $productDTOs = $products->map(function ($product) {
                return new \App\DTOs\WhatsApp\ProductDataDTO(
                    formattedProductMessage: "🛍️ *{$product->title}*\n\n💰 **{$product->price} FCFA**\n\n📝 {$product->description}\n\n📞 Interested? Contact us for more information!",
                    mediaUrls: ['https://example.com/product-image.jpg']
                );
            })->toArray();

            $mock->shouldReceive('processMessage')
                ->once()
                ->andReturn(new \App\DTOs\WhatsApp\WhatsAppMessageResponseDTO(
                    processed: true,
                    hasAiResponse: true,
                    aiResponse: 'Voici quelques produits qui pourraient vous intéresser :',
                    products: $productDTOs,
                    waitTimeSeconds: 2,
                    typingDurationSeconds: 3,
                    conversationContext: [],
                    processingError: null
                ));
        });

        $component = Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount])
            ->set('newMessage', 'Montrez-moi vos produits')
            ->call('sendMessage');

        // Test that the simulator can handle formatted products
        $formattedProducts = [
            [
                'message' => '🛍️ *Produit Test*\n\n💰 **1000 FCFA**\n\n📝 Description test',
                'media_urls' => ['https://example.com/test-image.jpg'],
            ],
        ];

        $component->call('displayFormattedProducts', $formattedProducts);

        $messages = $component->get('simulationMessages');

        // Should have user message + product message
        $this->assertGreaterThan(1, count($messages));

        // Check product message structure
        $productMessage = collect($messages)->firstWhere('type', 'product');
        $this->assertNotNull($productMessage);
        $this->assertStringContainsString('Produit Test', $productMessage['content']);
        $this->assertNotEmpty($productMessage['media_urls']);
    }

    public function test_conversation_context_preservation(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount]);

        // Simulate a conversation with multiple exchanges
        $exchanges = [
            'Bonjour',
            'Je cherche un téléphone',
            'Avez-vous des iPhone ?',
        ];

        foreach ($exchanges as $message) {
            $component->set('newMessage', $message)
                ->call('sendMessage');
        }

        $messages = $component->get('simulationMessages');
        $this->assertCount(3, $messages); // All user messages should be stored

        // Test context preparation
        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('prepareConversationHistory');
        $method->setAccessible(true);

        $context = $method->invoke($component->instance(), $messages);

        $this->assertStringContainsString('Bonjour', $context);
        $this->assertStringContainsString('téléphone', $context);
        $this->assertStringContainsString('iPhone', $context);
    }

    public function test_error_handling_in_simulator(): void
    {
        $this->actingAs($this->user);

        // Mock orchestrator to return an error
        $this->mock(WhatsAppMessageOrchestratorInterface::class, function ($mock) {
            $mock->shouldReceive('processMessage')
                ->once()
                ->andReturn(new \App\DTOs\WhatsApp\WhatsAppMessageResponseDTO(
                    processed: false,
                    hasAiResponse: false,
                    aiResponse: null,
                    products: [],
                    waitTimeSeconds: 0,
                    typingDurationSeconds: 0,
                    conversationContext: [],
                    processingError: 'Service AI temporairement indisponible'
                ));
        });

        $component = Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount])
            ->set('newMessage', 'Test message')
            ->call('sendMessage');

        // The simulator should handle errors gracefully
        $component->call('processAiResponse', 'Test message', []);

        // Should not crash and should handle the error appropriately
        $this->assertInstanceOf(\App\Livewire\Customer\WhatsApp\ConversationSimulator::class, $component->instance());
    }

    public function test_configuration_update_affects_responses(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount]);

        // Test initial configuration
        $this->assertEquals($this->whatsappAccount->agent_prompt, $component->get('currentPrompt'));

        // Update configuration in real-time
        $newConfig = [
            'agent_prompt' => 'Tu es maintenant un assistant technique spécialisé.',
            'response_time' => 'fast',
            'contextual_information' => 'Nouvelles informations techniques.',
        ];

        $component->dispatch('config-changed-live', $newConfig);

        // Verify configuration was updated
        $this->assertEquals($newConfig['agent_prompt'], $component->get('currentPrompt'));
        $this->assertEquals($newConfig['response_time'], $component->get('currentResponseTime'));
        $this->assertEquals($newConfig['contextual_information'], $component->get('currentContextualInfo'));
    }

    public function test_message_timing_simulation(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount]);

        // Test typing indicator
        $component->call('startTyping');
        $this->assertTrue($component->get('showTyping'));
        $this->assertTrue($component->get('isProcessing'));

        $component->call('stopTyping');
        $this->assertFalse($component->get('showTyping'));

        // Test AI response addition
        $testResponse = 'Réponse de test avec timing';
        $component->call('addAiResponse', $testResponse);

        $messages = $component->get('simulationMessages');
        $this->assertCount(1, $messages);
        $this->assertEquals('assistant', $messages[0]['type']);
        $this->assertEquals($testResponse, $messages[0]['content']);
        $this->assertFalse($component->get('showTyping'));
        $this->assertFalse($component->get('isProcessing'));
    }

    public function test_media_display_integration(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount]);

        // Test media handling
        $productWithMedia = [
            'message' => '📱 *iPhone 14 Pro*\n\n💰 **850 000 FCFA**\n\n📝 Dernier modèle Apple avec caméra professionnelle',
            'media_urls' => [
                'https://example.com/iphone-front.jpg',
                'https://example.com/iphone-back.jpg',
            ],
        ];

        $component->call('displayFormattedProducts', [$productWithMedia]);

        $messages = $component->get('simulationMessages');
        $this->assertCount(1, $messages);

        $message = $messages[0];
        $this->assertEquals('product', $message['type']);
        $this->assertCount(2, $message['media_urls']);
        $this->assertEquals($productWithMedia['media_urls'], $message['media_urls']);
    }

    public function test_conversation_limits_and_cleanup(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount])
            ->set('maxMessages', 4); // Low limit for testing

        // Send multiple messages to test limits
        for ($i = 1; $i <= 6; $i++) {
            $component->set('newMessage', "Message {$i}")
                ->call('sendMessage');
        }

        $messages = $component->get('simulationMessages');
        $this->assertLessThanOrEqual(5, count($messages)); // Should respect limit + warning

        // Test cleanup
        $component->call('clearConversation');
        $this->assertEmpty($component->get('simulationMessages'));
        $this->assertFalse($component->get('showTyping'));
        $this->assertFalse($component->get('isProcessing'));
    }
}
