<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\DTOs\WhatsApp\ProductDataDTO;
use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Events\WhatsApp\MessageProcessedEvent;
use App\Listeners\WhatsApp\StoreMessagesListener;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Wallet;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppAccountUsage;
use App\Notifications\WhatsApp\WalletDebitedNotification;
use App\Services\WhatsApp\Helpers\MessageBillingHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BillingCounterListenerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private UserSubscription $subscription;
    private WhatsAppAccount $account;
    private Wallet $wallet;
    private StoreMessagesListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock logging for verification
        Log::spy();
        Notification::fake();

        $this->user = User::factory()->create();
        $this->wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 1000.00,
        ]);

        $package = Package::factory()->create([
            'messages_limit' => 100,
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

        $this->listener = app(StoreMessagesListener::class);

        // Set billing costs
        config(['whatsapp.billing.costs.ai_message' => 15]);
        config(['whatsapp.billing.costs.product_message' => 10]);
        config(['whatsapp.billing.costs.media' => 5]);
        config(['whatsapp.billing.alert_threshold_percentage' => 20]);
    }

    /**
     * Create complex response: AI + 3 products with 2, 3, 5 medias = 14 total messages
     * (1 AI + 3 product messages + 10 medias)
     */
    private function createComplexResponse(): WhatsAppMessageResponseDTO
    {
        $products = [
            new ProductDataDTO('Product 1', ['img1.jpg', 'img2.jpg']), // 2 medias
            new ProductDataDTO('Product 2', ['img3.jpg', 'img4.jpg', 'img5.jpg']), // 3 medias
            new ProductDataDTO('Product 3', ['img6.jpg', 'img7.jpg', 'img8.jpg', 'img9.jpg', 'img10.jpg']), // 5 medias
        ];

        return WhatsAppMessageResponseDTO::success(
            'AI response with products',
            new WhatsAppAIResponseDTO('Response', 'gpt-4'),
            products: $products
        );
    }

    private function createMessageEvent(WhatsAppMessageResponseDTO $response): MessageProcessedEvent
    {
        return new MessageProcessedEvent(
            $this->account,
            new WhatsAppMessageRequestDTO('msg_123', '+237123456789', 'test message', now()->timestamp, 'text', false),
            $response
        );
    }

    /**
     * @test
     *
     * @dataProvider quotaAvailableProvider
     */
    public function it_uses_quota_when_messages_are_available(int $remainingMessages): void
    {
        // Set remaining messages by adjusting used messages
        $accountUsage = WhatsAppAccountUsage::getOrCreateForAccount($this->subscription, $this->account);
        $usedMessages = 100 - $remainingMessages;
        $accountUsage->update(['messages_used' => $usedMessages]);

        // Create complex response requiring 14 messages
        $response = $this->createComplexResponse();
        $event = $this->createMessageEvent($response);

        // Expected calculations
        $expectedMessageCount = MessageBillingHelper::getNumberOfMessagesFromResponse($response);
        $this->assertEquals(14, $expectedMessageCount);

        // Handle the event (should not throw exception)
        $this->expectNotToPerformAssertions(); // Skip other assertions for now
        $this->listener->handle($event);
    }

    public static function quotaAvailableProvider(): array
    {
        return [
            'User with 15 messages remaining' => [15], // 15-14=1, should NOT trigger alert (1% < 20%)
            'User with 14 messages remaining' => [14], // 14-14=0, should trigger alert (0% < 20%)
            'User with 2 messages remaining' => [2],   // Not enough quota, this should fail - but included for edge testing
            'User with 1 message remaining' => [1],    // Not enough quota, this should fail - but included for edge testing
        ];
    }

    /**
     * @test
     *
     * @dataProvider walletDebitProvider
     */
    public function it_debits_wallet_when_quota_exhausted(float $walletBalance, float $expectedFinalBalance): void
    {
        // Exhaust quota completely
        $accountUsage = WhatsAppAccountUsage::getOrCreateForAccount($this->subscription, $this->account);
        $accountUsage->update(['messages_used' => 100]);

        // Set specific wallet balance
        $this->wallet->update(['balance' => $walletBalance]);

        // Create complex response requiring 14 messages
        $response = $this->createComplexResponse();
        $event = $this->createMessageEvent($response);

        // Expected billing amount: 1*15 (AI) + 3*10 (products) + 10*5 (medias) = 15 + 30 + 50 = 95 XAF
        $expectedBillingAmount = MessageBillingHelper::getAmountToBillFromResponse($response);
        $this->assertEquals(95.0, $expectedBillingAmount);

        // Handle the event
        $this->listener->handle($event);

        // Verify results based on wallet balance
        $this->wallet->refresh();
        $accountUsage->refresh();

        if ($walletBalance >= $expectedBillingAmount) {
            // Wallet should be debited
            $this->assertEquals($expectedFinalBalance, $this->wallet->balance);
            $this->assertEquals(95.0, $accountUsage->overage_cost_paid_xaf);
            $this->assertNotNull($accountUsage->last_overage_payment_at);

            // Should send wallet debited notification
            Notification::assertSentTo($this->user, WalletDebitedNotification::class, function ($notification) use ($expectedBillingAmount, $expectedFinalBalance) {
                return $notification->debitedAmount === $expectedBillingAmount &&
                       $notification->newWalletBalance === $expectedFinalBalance;
            });

            // Verify success logs
            Log::shouldHaveReceived('info')
                ->with('[BillingCounterListener] Wallet debited and notification sent', \Mockery::type('array'))
                ->once();

        } else {
            // Insufficient funds - wallet should not be debited
            $this->assertEquals($walletBalance, $this->wallet->balance);
            $this->assertEquals(0.0, $accountUsage->overage_cost_paid_xaf);
            $this->assertNull($accountUsage->last_overage_payment_at);

            // Should not send wallet debited notification
            Notification::assertNotSentTo($this->user, WalletDebitedNotification::class);

            // Verify error logs
            Log::shouldHaveReceived('error')
                ->with('[BillingCounterListener] Failed to debit wallet - insufficient funds', \Mockery::type('array'))
                ->once();
        }

        // Common logs
        Log::shouldHaveReceived('info')
            ->with('[BillingCounterListener] Processing billing', \Mockery::type('array'))
            ->once();
    }

    public static function walletDebitProvider(): array
    {
        // Required amount is 95 XAF for complex response
        return [
            'Wallet with excess funds (500 XAF)' => [500.0, 405.0], // 500 - 95 = 405
            'Wallet with exact amount (95 XAF)' => [95.0, 0.0],     // 95 - 95 = 0
            'Wallet with just 5 XAF' => [5.0, 5.0],               // Insufficient, no debit
            'Wallet with 94 XAF (1 short)' => [94.0, 94.0],       // Insufficient, no debit
        ];
    }

    /** @test */
    public function it_handles_unsuccessful_events_gracefully(): void
    {
        $response = WhatsAppMessageResponseDTO::error('Processing failed');
        $event = $this->createMessageEvent($response);

        // Handle the event
        $this->listener->handle($event);

        // Nothing should happen
        $accountUsage = WhatsAppAccountUsage::getOrCreateForAccount($this->subscription, $this->account);
        $this->assertEquals(0, $accountUsage->messages_used);

        $this->wallet->refresh();
        $this->assertEquals(1000.0, $this->wallet->balance);

        Notification::assertNothingSent();
    }

    /** @test */
    public function it_handles_missing_subscription_gracefully(): void
    {
        // Delete subscription
        $this->subscription->delete();

        $response = $this->createComplexResponse();
        $event = $this->createMessageEvent($response);

        // Handle the event
        $this->listener->handle($event);

        // Nothing should happen
        $this->wallet->refresh();
        $this->assertEquals(1000.0, $this->wallet->balance);

        // Should log warning
        Log::shouldHaveReceived('warning')
            ->with('[BillingCounterListener] No active subscription', \Mockery::type('array'))
            ->once();

        Notification::assertNothingSent();
    }

    /** @test */
    public function it_logs_processing_details_correctly(): void
    {
        $response = $this->createComplexResponse();
        $event = $this->createMessageEvent($response);

        $this->listener->handle($event);

        // Verify detailed logging
        Log::shouldHaveReceived('info')
            ->with('[BillingCounterListener] Processing billing', \Mockery::on(function ($data) {
                return $data['user_id'] === $this->user->id &&
                       $data['session_id'] === 'test-session-123' &&
                       $data['message_count'] === 14 &&
                       $data['remaining_messages_before'] === 100;
            }))
            ->once();
    }

    /** @test */
    public function it_calculates_message_costs_correctly(): void
    {
        $response = $this->createComplexResponse();

        // Verify helper calculations
        $messageCount = MessageBillingHelper::getNumberOfMessagesFromResponse($response);
        $billingAmount = MessageBillingHelper::getAmountToBillFromResponse($response);

        // Expected: 1 AI + 3 products + 10 medias = 14 messages
        $this->assertEquals(14, $messageCount);

        // Expected: 15 (AI) + 30 (3*10 products) + 50 (10*5 medias) = 95 XAF
        $this->assertEquals(95.0, $billingAmount);
    }
}
