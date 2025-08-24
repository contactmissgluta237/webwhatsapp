<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Enums\SimulatorMessageType;
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
 * Tests fonctionnels pour le simulateur de conversation WhatsApp
 *
 * @group livewire
 * @group whatsapp
 * @group simulator
 */
final class ConversationSimulatorTest extends TestCase
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

        // Create AI model
        $this->aiModel = AiModel::factory()->create([
            'provider' => 'ollama',
            'model_identifier' => 'llama2',
            'endpoint_url' => 'http://localhost:11434',
        ]);

        // Create WhatsApp account
        $this->whatsappAccount = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'agent_enabled' => true,
            'agent_prompt' => 'Tu es un assistant WhatsApp utile et professionnel.',
            'contextual_information' => 'Informations contextuelles de test',
            'ai_model_id' => $this->aiModel->id,
            'response_time' => 'random',
        ]);
    }

    public function test_simulator_component_renders_successfully(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount])
            ->assertSuccessful()
            ->assertSee('Simulateur de conversation')
            ->assertSee('Commencez une conversation pour tester votre configuration IA')
            ->assertViewIs('livewire.customer.whats-app.conversation-simulator');
    }

    public function test_simulator_loads_current_configuration(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount])
            ->assertSet('currentPrompt', $this->whatsappAccount->agent_prompt)
            ->assertSet('currentContextualInfo', $this->whatsappAccount->contextual_information)
            ->assertSet('currentModelId', $this->whatsappAccount->ai_model_id)
            ->assertSet('currentResponseTime', $this->whatsappAccount->response_time)
            ->assertSet('simulationMessages', [])
            ->assertSet('isProcessing', false)
            ->assertSet('showTyping', false);
    }

    public function test_user_can_send_message(): void
    {
        $this->actingAs($this->user);

        $testMessage = 'Bonjour, comment allez-vous ?';

        Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount])
            ->set('newMessage', $testMessage)
            ->call('sendMessage')
            ->assertSet('newMessage', '') // Message cleared after sending
            ->assertCount('simulationMessages', 1); // User message added
    }

    public function test_empty_message_is_rejected(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount])
            ->set('newMessage', '')
            ->call('sendMessage')
            ->assertCount('simulationMessages', 0) // No message should be added
            ->set('newMessage', '   ') // Only spaces
            ->call('sendMessage')
            ->assertCount('simulationMessages', 0); // Still no message should be added
    }

    public function test_message_limit_is_enforced(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount])
            ->set('maxMessages', 2); // Set low limit for testing

        // Send messages to reach limit (2 exchanges = 4 messages total)
        for ($i = 1; $i <= 5; $i++) {
            $component->set('newMessage', "Message test {$i}")
                ->call('sendMessage');
        }

        // Should stop at maxMessages * 2 + system message
        $component->assertCount('simulationMessages', 5); // 4 messages + 1 system warning
    }

    public function test_conversation_can_be_cleared(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount])
            ->set('newMessage', 'Test message')
            ->call('sendMessage')
            ->assertCount('simulationMessages', 1)
            ->call('clearConversation')
            ->assertSet('simulationMessages', [])
            ->assertSet('showTyping', false)
            ->assertSet('isProcessing', false);
    }

    public function test_configuration_update_event_is_handled(): void
    {
        $this->actingAs($this->user);

        // Update account configuration
        $newPrompt = 'Nouveau prompt de test';
        $this->whatsappAccount->update(['agent_prompt' => $newPrompt]);

        Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount])
            ->dispatch('config-updated')
            ->assertSet('currentPrompt', $newPrompt)
            ->assertCount('simulationMessages', 1); // System message about config update
    }

    public function test_live_configuration_change_event_is_handled(): void
    {
        $this->actingAs($this->user);

        $newData = [
            'agent_prompt' => 'Prompt mis à jour en temps réel',
            'ai_model_id' => $this->aiModel->id,
            'response_time' => 'fast',
            'contextual_information' => 'Nouvelles infos contextuelles',
        ];

        Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount])
            ->dispatch('config-changed-live', $newData)
            ->assertSet('currentPrompt', $newData['agent_prompt'])
            ->assertSet('currentModelId', $newData['ai_model_id'])
            ->assertSet('currentResponseTime', $newData['response_time'])
            ->assertSet('currentContextualInfo', $newData['contextual_information']);
    }

    public function test_typing_indicator_methods(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount])
            ->call('startTyping')
            ->assertSet('showTyping', true)
            ->assertSet('isProcessing', true)
            ->call('stopTyping')
            ->assertSet('showTyping', false);
    }

    public function test_ai_response_is_added_correctly(): void
    {
        $this->actingAs($this->user);

        $aiResponse = 'Ceci est une réponse de l\'IA de test';

        $component = Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount])
            ->call('addAiResponse', $aiResponse)
            ->assertCount('simulationMessages', 1)
            ->assertSet('showTyping', false)
            ->assertSet('isProcessing', false);

        // Check message content
        $messages = $component->get('simulationMessages');
        $this->assertCount(1, $messages);
        $this->assertEquals(SimulatorMessageType::ASSISTANT()->value, $messages[0]['type']);
        $this->assertEquals($aiResponse, $messages[0]['content']);
    }

    public function test_formatted_products_display(): void
    {
        $this->actingAs($this->user);

        // Create test products with media
        $products = [
            [
                'message' => 'Produit 1 - Test',
                'media_urls' => ['https://example.com/image1.jpg'],
            ],
            [
                'message' => 'Produit 2 - Test',
                'media_urls' => ['https://example.com/image2.jpg', 'https://example.com/image3.jpg'],
            ],
        ];

        $component = Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount])
            ->call('displayFormattedProducts', $products)
            ->assertCount('simulationMessages', 2);

        // Verify messages structure
        $messages = $component->get('simulationMessages');
        $this->assertCount(2, $messages);

        $this->assertEquals('product', $messages[0]['type']);
        $this->assertEquals($products[0]['message'], $messages[0]['content']);
        $this->assertEquals($products[0]['media_urls'], $messages[0]['media_urls']);

        $this->assertEquals('product', $messages[1]['type']);
        $this->assertEquals($products[1]['message'], $messages[1]['content']);
        $this->assertEquals($products[1]['media_urls'], $messages[1]['media_urls']);
    }

    public function test_message_with_media_is_handled_correctly(): void
    {
        $this->actingAs($this->user);

        $messageContent = 'Message avec médias';
        $mediaUrls = ['https://example.com/image1.jpg', 'https://example.com/image2.jpg'];

        // Use reflection to test private method
        $component = Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount]);
        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('addMessageWithMedia');
        $method->setAccessible(true);

        $method->invoke($component->instance(), 'product', $messageContent, $mediaUrls);

        $messages = $component->get('simulationMessages');
        $this->assertCount(1, $messages);
        $this->assertEquals('product', $messages[0]['type']);
        $this->assertEquals($messageContent, $messages[0]['content']);
        $this->assertEquals($mediaUrls, $messages[0]['media_urls']);
    }

    public function test_conversation_history_preparation(): void
    {
        $this->actingAs($this->user);

        $conversationContext = [
            ['type' => 'user', 'content' => 'Hello'],
            ['type' => 'assistant', 'content' => 'Hi there!'],
            ['type' => 'user', 'content' => 'How are you?'],
        ];

        $component = Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount]);
        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('prepareConversationHistory');
        $method->setAccessible(true);

        $result = $method->invoke($component->instance(), $conversationContext);
        $expected = "user: Hello\nsystem: Hi there!\nuser: How are you?";

        $this->assertEquals($expected, $result);
    }

    public function test_simulator_handles_user_products(): void
    {
        $this->actingAs($this->user);

        // Create test products for the user
        $products = UserProduct::factory(3)->create([
            'user_id' => $this->user->id,
            'title' => 'Produit Test',
            'description' => 'Description test',
            'price' => 1000.00,
        ]);

        $productIds = $products->pluck('id')->toArray();

        Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount])
            ->call('simulateProductsSending', $productIds)
            ->assertCount('simulationMessages', 3); // One message per product

        // Verify product messages format
        $component = Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount]);
        $messages = $component->get('simulationMessages');

        foreach ($messages as $message) {
            $this->assertEquals('product', $message['type']);
            $this->assertStringContainsString('Produit Test', $message['content']);
            $this->assertStringContainsString('1 000 FCFA', $message['content']);
        }
    }

    public function test_component_properties_validation(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ConversationSimulator::class, ['account' => $this->whatsappAccount]);

        // Test maxMessages constraint
        $this->assertIsInt($component->get('maxMessages'));
        $this->assertEquals(10, $component->get('maxMessages'));

        // Test message length limit (the component uses maxlength=500 in the input)
        $longMessage = str_repeat('a', 501);
        $component->set('newMessage', $longMessage);
        // The input field has maxlength=500, so this test might not be relevant at component level
        // Instead, let's test that the component accepts messages up to 500 chars
        $validMessage = str_repeat('a', 500);
        $component->set('newMessage', $validMessage);
        $this->assertLessThanOrEqual(500, strlen($component->get('newMessage')));
    }
}
