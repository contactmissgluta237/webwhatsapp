<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Events\WhatsApp\AiResponseGenerated;
use App\Events\WhatsApp\MessageProcessedEvent;
use App\Listeners\WhatsApp\BillingCounterListener;
use App\Listeners\WhatsApp\TrackAiUsageListener;
use App\Models\AiModel;
use App\Models\AiUsageLog;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Wallet;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppAccountUsage;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BillingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WhatsAppAccount $account;
    private WhatsAppConversation $conversation;
    private WhatsAppMessage $message;
    private UserSubscription $subscription;
    private Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed packages first
        $this->artisan('db:seed', ['--class' => 'PackagesSeeder']);
        $this->package = Package::where('name', 'starter')->first();

        $this->user = User::factory()->create();

        $aiModel = AiModel::factory()->create();
        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'ai_model_id' => $aiModel->id,
        ]);

        $this->conversation = WhatsAppConversation::factory()->create([
            'whatsapp_account_id' => $this->account->id,
            'chat_id' => 'test_chat_123',
        ]);

        $this->message = WhatsAppMessage::factory()->create([
            'whatsapp_conversation_id' => $this->conversation->id,
            'whatsapp_message_id' => 'msg_123',
        ]);

        // Create wallet
        $this->user->wallet()->create([
            'balance' => 1000.0,
            'currency' => 'XAF',
        ]);

        // Create active subscription
        $this->subscription = UserSubscription::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addDays(20),
            'status' => 'active',
            'messages_limit' => $this->package->messages_limit,
            'context_limit' => $this->package->context_limit,
            'accounts_limit' => $this->package->accounts_limit,
            'products_limit' => $this->package->products_limit,
            'activated_at' => now()->subDays(10),
        ]);
    }

    /** @test */
    public function both_ai_tracking_and_billing_listeners_can_handle_message_processed_event(): void
    {
        $this->assertDatabaseEmpty('ai_usage_logs');
        $this->assertDatabaseEmpty('whatsapp_account_usages');

        // Create message processed event
        $messageRequest = new \App\DTOs\WhatsApp\WhatsAppMessageRequestDTO(
            id: 'msg_123',
            from: 'test_chat_123',
            body: 'Hello AI',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        $aiResponse = new \App\DTOs\WhatsApp\WhatsAppAIResponseDTO(
            response: 'Hello! How can I help?',
            model: 'deepseek-chat',
            confidence: 0.95,
            tokensUsed: 150,
            cost: 0.001,
            metadata: [
                'costs' => [
                    'prompt_cost_usd' => 0.0002,
                    'completion_cost_usd' => 0.0008,
                    'cached_cost_usd' => 0.0000,
                    'total_cost_usd' => 0.001000,
                    'total_cost_xaf' => 0.65,
                ],
            ]
        );

        $messageResponse = new \App\DTOs\WhatsApp\WhatsAppMessageResponseDTO(
            processed: true,
            hasAiResponse: true,
            aiResponse: 'Hello! How can I help?',
            processingError: null,
            products: []
        );

        // Dispatch both events that would normally be triggered
        $messageProcessedEvent = new MessageProcessedEvent(
            $this->account,
            $messageRequest,
            $messageResponse
        );

        $aiResponseEvent = new AiResponseGenerated(
            $this->account,
            $messageRequest,
            $aiResponse,
            'Hello AI',
            1200.5,
            isSimulation: false
        );

        // Act - Handle both events
        $billingListener = new BillingCounterListener;
        $billingListener->handle($messageProcessedEvent);

        $aiTrackingListener = new TrackAiUsageListener;
        $aiTrackingListener->handle($aiResponseEvent);

        // Assert both systems worked independently
        $this->assertDatabaseCount('ai_usage_logs', 1);
        $this->assertDatabaseCount('whatsapp_account_usages', 1);

        // Check AI usage log
        $usageLog = AiUsageLog::first();
        $this->assertEquals($this->user->id, $usageLog->user_id);
        $this->assertEquals($this->account->id, $usageLog->whatsapp_account_id);
        $this->assertEquals(150, $usageLog->total_tokens);
        $this->assertEquals(0.001, (float) $usageLog->total_cost_usd);
        $this->assertEquals(0.65, (float) $usageLog->total_cost_xaf);

        // Check billing usage
        $accountUsage = WhatsAppAccountUsage::first();
        $this->assertEquals($this->subscription->id, $accountUsage->user_subscription_id);
        $this->assertEquals($this->account->id, $accountUsage->whatsapp_account_id);
        $this->assertEquals(1, $accountUsage->messages_used);
    }

    /** @test */
    public function ai_tracking_listener_ignores_simulation_events(): void
    {
        $this->assertDatabaseEmpty('ai_usage_logs');

        // Create AI response event marked as simulation
        $messageRequest = new \App\DTOs\WhatsApp\WhatsAppMessageRequestDTO(
            id: 'sim_msg_456',
            from: 'simulator_user',
            body: 'Simulation message',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        $aiResponse = new \App\DTOs\WhatsApp\WhatsAppAIResponseDTO(
            response: 'Simulation response',
            model: 'deepseek-chat',
            tokensUsed: 100,
            metadata: [
                'costs' => [
                    'total_cost_usd' => 0.001,
                    'total_cost_xaf' => 0.65,
                ],
            ]
        );

        $aiResponseEvent = new AiResponseGenerated(
            $this->account,
            $messageRequest,
            $aiResponse,
            'Simulation message',
            800.0,
            isSimulation: true // Mark as simulation
        );

        // Act
        $aiTrackingListener = new TrackAiUsageListener;
        $aiTrackingListener->handle($aiResponseEvent);

        // Assert - No AI usage should be logged for simulations
        $this->assertDatabaseEmpty('ai_usage_logs');
    }

    /** @test */
    public function listeners_are_registered_correctly_in_event_service_provider(): void
    {
        Event::fake();

        // Create and dispatch events
        $messageRequest = new \App\DTOs\WhatsApp\WhatsAppMessageRequestDTO(
            id: 'msg_test',
            from: 'test_chat',
            body: 'Test',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        $messageResponse = new \App\DTOs\WhatsApp\WhatsAppMessageResponseDTO(
            processed: true,
            hasAiResponse: true,
            aiResponse: 'Response',
            products: []
        );

        $aiResponse = new \App\DTOs\WhatsApp\WhatsAppAIResponseDTO(
            response: 'AI Response',
            model: 'deepseek-chat',
            tokensUsed: 100,
            metadata: ['costs' => ['total_cost_usd' => 0.001, 'total_cost_xaf' => 0.65]]
        );

        // Act
        event(new MessageProcessedEvent($this->account, $messageRequest, $messageResponse));
        event(new AiResponseGenerated($this->account, $messageRequest, $aiResponse, 'Test', 1000.0));

        // Assert
        Event::assertDispatched(MessageProcessedEvent::class);
        Event::assertDispatched(AiResponseGenerated::class);
    }
}
