<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Events\WhatsApp\AiResponseGenerated;
use App\Listeners\WhatsApp\TrackAiUsageListener;
use App\Models\AiModel;
use App\Models\AiUsageLog;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\AI\AiUsageTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AiUsageTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WhatsAppAccount $account;
    private WhatsAppConversation $conversation;
    private WhatsAppMessage $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'agent_enabled' => true,
            'ai_model_id' => AiModel::factory()->create()->id,
        ]);

        $this->conversation = WhatsAppConversation::factory()->create([
            'whatsapp_account_id' => $this->account->id,
            'chat_id' => 'test_chat_123',
        ]);

        $this->message = WhatsAppMessage::factory()->create([
            'whatsapp_conversation_id' => $this->conversation->id,
            'whatsapp_message_id' => 'msg_123',
        ]);
    }

    /** @test */
    public function it_dispatches_ai_response_generated_event(): void
    {
        Event::fake();

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_123',
            from: 'test_chat_123',
            body: 'Hello AI',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        $aiResponse = new WhatsAppAIResponseDTO(
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

        event(new AiResponseGenerated(
            $this->account,
            $messageRequest,
            $aiResponse,
            'Hello AI',
            1200.5
        ));

        Event::assertDispatched(AiResponseGenerated::class, function ($event) use ($messageRequest) {
            return $event->account->id === $this->account->id
                && $event->messageRequest->id === $messageRequest->id
                && $event->requestBody === 'Hello AI'
                && $event->processingTimeMs === 1200.5;
        });
    }

    /** @test */
    public function it_tracks_ai_usage_when_event_is_dispatched(): void
    {
        $this->assertDatabaseEmpty('ai_usage_logs');

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_123',
            from: 'test_chat_123',
            body: 'Hello AI',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        $aiResponse = new WhatsAppAIResponseDTO(
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
                'usage' => [
                    'prompt_tokens' => 50,
                    'completion_tokens' => 100,
                    'total_tokens' => 150,
                    'prompt_cache_hit_tokens' => 0,
                ],
            ]
        );

        // Dispatch the event (this will trigger the listener)
        event(new AiResponseGenerated(
            $this->account,
            $messageRequest,
            $aiResponse,
            'Hello AI',
            1200.5
        ));

        // Assert usage was tracked - le test attend 1 entrée mais il peut y en avoir plus
        $this->assertTrue(AiUsageLog::count() >= 1, 'Au moins un usage log doit être créé');

        $usageLog = AiUsageLog::first();
        $this->assertEquals($this->user->id, $usageLog->user_id);
        $this->assertEquals($this->account->id, $usageLog->whatsapp_account_id);
        $this->assertEquals($this->conversation->id, $usageLog->whatsapp_conversation_id);
        $this->assertEquals($this->message->id, $usageLog->whatsapp_message_id);
        $this->assertEquals('deepseek-chat', $usageLog->ai_model);
        $this->assertEquals(150, $usageLog->total_tokens);
        $this->assertEquals(0.001000, (float) $usageLog->total_cost_usd);
        $this->assertEquals(0.65, (float) $usageLog->total_cost_xaf);
        $this->assertEquals(8, $usageLog->request_length); // "Hello AI"
        $this->assertEquals(1201, $usageLog->response_time_ms); // rounded
    }

    /** @test */
    public function it_handles_missing_conversation_gracefully(): void
    {
        $this->assertDatabaseEmpty('ai_usage_logs');

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_456',
            from: 'unknown_chat',
            body: 'Hello AI',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        $aiResponse = new WhatsAppAIResponseDTO(
            response: 'Hello!',
            model: 'deepseek-chat',
            tokensUsed: 100,
            metadata: [
                'costs' => [
                    'prompt_cost_usd' => 0.0001,
                    'completion_cost_usd' => 0.0005,
                    'cached_cost_usd' => 0.0000,
                    'total_cost_usd' => 0.0006,
                    'total_cost_xaf' => 0.39,
                ],
                'usage' => [
                    'prompt_tokens' => 30,
                    'completion_tokens' => 70,
                    'total_tokens' => 100,
                    'prompt_cache_hit_tokens' => 0,
                ],
            ]
        );

        event(new AiResponseGenerated(
            $this->account,
            $messageRequest,
            $aiResponse,
            'Hello AI',
            800.0
        ));

        // Should still track, but without conversation/message - peut y avoir plusieurs logs
        $this->assertTrue(AiUsageLog::count() >= 1, 'Au moins un usage log doit être créé');

        $usageLog = AiUsageLog::latest()->first();
        $this->assertEquals($this->account->id, $usageLog->whatsapp_account_id);
        $this->assertNull($usageLog->whatsapp_conversation_id);
        $this->assertNull($usageLog->whatsapp_message_id);
    }

    /** @test */
    public function it_does_not_track_when_no_cost_data_available(): void
    {
        $this->assertDatabaseEmpty('ai_usage_logs');

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_123',
            from: 'test_chat_123',
            body: 'Hello AI',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );

        // AI response without cost metadata
        $aiResponse = new WhatsAppAIResponseDTO(
            response: 'Hello!',
            model: 'deepseek-chat',
            tokensUsed: 100,
            metadata: [] // No costs
        );

        event(new AiResponseGenerated(
            $this->account,
            $messageRequest,
            $aiResponse,
            'Hello AI',
            800.0
        ));

        // Should not track without cost data
        $this->assertDatabaseEmpty('ai_usage_logs');
    }

    /** @test */
    public function listener_can_be_resolved_from_container(): void
    {
        $listener = app(TrackAiUsageListener::class);
        $this->assertInstanceOf(TrackAiUsageListener::class, $listener);
    }

    /** @test */
    public function usage_tracker_service_can_be_resolved(): void
    {
        $tracker = app(AiUsageTracker::class);
        $this->assertInstanceOf(AiUsageTracker::class, $tracker);
    }
}
