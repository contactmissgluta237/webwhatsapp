<?php

declare(strict_types=1);

namespace Tests\E2E;

use App\DTOs\WhatsApp\ProductDataDTO;
use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Events\WhatsApp\MessageProcessedEvent;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Wallet;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppAccountUsage;
use App\Notifications\WhatsApp\LowQuotaNotification;
use App\Notifications\WhatsApp\WalletDebitedNotification;
use App\Services\WhatsApp\Helpers\MessageBillingHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BillingSystemE2ETest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private UserSubscription $subscription;
    private WhatsAppAccount $account;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test environment
        $this->user = User::factory()->create();
        $this->wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 1000.00, // 1000 XAF
        ]);

        $package = Package::factory()->create([
            'messages_limit' => 100,
            'price' => 500,
        ]);

        $this->subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'messages_limit' => 100,
            'starts_at' => now()->subDays(1),
            'ends_at' => now()->addDays(30),
            'status' => 'active',
        ]);

        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'session_id' => 'test-session-123',
        ]);

        // Set billing costs for tests
        config(['whatsapp.billing.costs.ai_message' => 15]);
        config(['whatsapp.billing.costs.product_message' => 10]);
        config(['whatsapp.billing.costs.media' => 5]);
        config(['whatsapp.billing.alert_threshold_percentage' => 20]);
    }

    /** @test */
    public function it_calculates_message_costs_correctly_with_helper()
    {
        // Test AI message only
        $aiOnlyResponse = WhatsAppMessageResponseDTO::success(
            'Hello from AI',
            new WhatsAppAIResponseDTO('Hello', []),
            products: []
        );

        $this->assertEquals(1, MessageBillingHelper::getNumberOfMessagesFromResponse($aiOnlyResponse));
        $this->assertEquals(15.0, MessageBillingHelper::getAmountToBillFromResponse($aiOnlyResponse));

        // Test AI + products with media
        $productWithMedia = new ProductDataDTO('Product message', ['image1.jpg', 'image2.jpg']);

        $fullResponse = WhatsAppMessageResponseDTO::success(
            'AI response',
            new WhatsAppAIResponseDTO('Response', []),
            products: [$productWithMedia]
        );

        // Expected: 1 AI + 1 product message + 2 media = 4 messages
        $this->assertEquals(4, MessageBillingHelper::getNumberOfMessagesFromResponse($fullResponse));
        // Expected: 15 (AI) + 10 (product) + 10 (2 media × 5) = 35 XAF
        $this->assertEquals(35.0, MessageBillingHelper::getAmountToBillFromResponse($fullResponse));
    }

    /** @test */
    public function it_uses_quota_when_messages_are_available()
    {
        Notification::fake();

        // Create AI response with 1 message
        $response = WhatsAppMessageResponseDTO::success(
            'AI response',
            new WhatsAppAIResponseDTO('Test', [])
        );

        $event = new MessageProcessedEvent(
            $this->account,
            new WhatsAppMessageRequestDTO('+237123456789', 'test message', now(), 'test-session'),
            $response
        );

        // Dispatch the event
        Event::dispatch($event);

        // Verify quota was used
        $this->subscription->refresh();
        $accountUsage = $this->subscription->getUsageForAccount($this->account);

        $this->assertEquals(1, $accountUsage->messages_used);
        $this->assertEquals(99, $this->subscription->getRemainingMessages());

        // Verify wallet was not touched
        $this->wallet->refresh();
        $this->assertEquals(1000.00, $this->wallet->balance);

        // Should not send notifications yet (not at alert threshold)
        Notification::assertNothingSent();
    }

    /** @test */
    public function it_sends_low_quota_alert_when_threshold_reached()
    {
        Notification::fake();

        // Use up quota to trigger alert (leave 10 messages = 10% of 100)
        $accountUsage = WhatsAppAccountUsage::getOrCreateForAccount($this->subscription, $this->account);
        $accountUsage->update(['messages_used' => 85]); // 15 remaining = 15%

        // Send one more message to trigger alert (20% threshold)
        $response = WhatsAppMessageResponseDTO::success(
            'AI response',
            new WhatsAppAIResponseDTO('Test', [])
        );

        $event = new MessageProcessedEvent(
            $this->account,
            new WhatsAppMessageRequestDTO('+237123456789', 'test', now(), 'test-session'),
            $response
        );

        Event::dispatch($event);

        // Verify quota used and alert sent
        $accountUsage->refresh();
        $this->assertEquals(86, $accountUsage->messages_used);
        $this->assertEquals(14, $this->subscription->fresh()->getRemainingMessages());

        // Should send low quota notification
        Notification::assertSentTo($this->user, LowQuotaNotification::class);
    }

    /** @test */
    public function it_debits_wallet_when_quota_exhausted()
    {
        Notification::fake();

        // Exhaust the quota
        $accountUsage = WhatsAppAccountUsage::getOrCreateForAccount($this->subscription, $this->account);
        $accountUsage->update(['messages_used' => 100]);

        // Create expensive response: AI + product with 2 media
        $productWithMedia = new ProductDataDTO('Product', ['img1.jpg', 'img2.jpg']);

        $response = WhatsAppMessageResponseDTO::success(
            'AI response with products',
            new WhatsAppAIResponseDTO('Response', []),
            products: [$productWithMedia]
        );

        $event = new MessageProcessedEvent(
            $this->account,
            new WhatsAppMessageRequestDTO('+237123456789', 'test', now(), 'test-session'),
            $response
        );

        Event::dispatch($event);

        // Verify wallet debited: 15 (AI) + 10 (product) + 10 (2×5 media) = 35 XAF
        $this->wallet->refresh();
        $this->assertEquals(965.00, $this->wallet->balance); // 1000 - 35

        // Verify overage tracking
        $accountUsage->refresh();
        $this->assertEquals(35.0, $accountUsage->overage_cost_paid_xaf);
        $this->assertNotNull($accountUsage->last_overage_payment_at);

        // Should send wallet debited notification
        Notification::assertSentTo($this->user, WalletDebitedNotification::class, function ($notification) {
            return $notification->debitedAmount === 35.0 && $notification->newWalletBalance === 965.0;
        });
    }

    /** @test */
    public function it_handles_insufficient_wallet_balance_gracefully()
    {
        Notification::fake();

        // Set low wallet balance
        $this->wallet->update(['balance' => 20.00]);

        // Exhaust quota
        $accountUsage = WhatsAppAccountUsage::getOrCreateForAccount($this->subscription, $this->account);
        $accountUsage->update(['messages_used' => 100]);

        // Try expensive operation requiring 35 XAF
        $productWithMedia = new ProductDataDTO('Product', ['img1.jpg', 'img2.jpg']);

        $response = WhatsAppMessageResponseDTO::success(
            'Expensive response',
            new WhatsAppAIResponseDTO('Response', []),
            products: [$productWithMedia]
        );

        $event = new MessageProcessedEvent(
            $this->account,
            new WhatsAppMessageRequestDTO('+237123456789', 'test', now(), 'test-session'),
            $response
        );

        Event::dispatch($event);

        // Verify wallet was not debited due to insufficient funds
        $this->wallet->refresh();
        $this->assertEquals(20.00, $this->wallet->balance);

        // Verify no overage was recorded
        $accountUsage->refresh();
        $this->assertEquals(0.0, $accountUsage->overage_cost_paid_xaf);

        // Should not send wallet debited notification
        Notification::assertNotSentTo($this->user, WalletDebitedNotification::class);
    }

    /** @test */
    public function it_processes_complex_billing_scenario_end_to_end()
    {
        Notification::fake();

        // Scenario: User starts with quota, gets alerts, then switches to wallet billing

        // Step 1: Normal usage - consume 82 messages (18 remaining = 18% < 20% threshold)
        $accountUsage = WhatsAppAccountUsage::getOrCreateForAccount($this->subscription, $this->account);
        $accountUsage->update(['messages_used' => 82]);

        // Step 2: Send message that triggers low quota alert
        $response = WhatsAppMessageResponseDTO::success('AI response', new WhatsAppAIResponseDTO('Test', []));
        $event = new MessageProcessedEvent(
            $this->account,
            new WhatsAppMessageRequestDTO('+237123456789', 'msg1', now(), 'session1'),
            $response
        );

        Event::dispatch($event);

        // Verify alert sent
        Notification::assertSentTo($this->user, LowQuotaNotification::class);
        $this->assertEquals(17, $this->subscription->fresh()->getRemainingMessages());

        // Step 3: Consume remaining quota
        for ($i = 0; $i < 17; $i++) {
            $accountUsage->increment('messages_used');
        }

        // Step 4: Send message requiring wallet debit
        $productWithMedia = new ProductDataDTO('Product', ['image.jpg']);
        $walletResponse = WhatsAppMessageResponseDTO::success(
            'AI with product',
            new WhatsAppAIResponseDTO('Response', []),
            products: [$productWithMedia]
        );

        $walletEvent = new MessageProcessedEvent(
            $this->account,
            new WhatsAppMessageRequestDTO('+237123456789', 'msg2', now(), 'session2'),
            $walletResponse
        );

        Event::dispatch($walletEvent);

        // Verify final state
        $accountUsage->refresh();
        $this->wallet->refresh();

        // Should have debited 15 (AI) + 10 (product) + 5 (media) = 30 XAF
        $this->assertEquals(970.0, $this->wallet->balance);
        $this->assertEquals(30.0, $accountUsage->overage_cost_paid_xaf);

        // Should have sent wallet debited notification
        Notification::assertSentTo($this->user, WalletDebitedNotification::class);
    }
}
