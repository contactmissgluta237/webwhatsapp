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
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
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

        // Only fake notifications, not logs
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

    #[Test]
    public function it_uses_subscription_quota_when_available(): void
    {
        // Keep subscription active (has quota)
        // Create simple response
        $response = WhatsAppMessageResponseDTO::success(
            'Simple AI response',
            new WhatsAppAIResponseDTO('Response', 'gpt-4')
        );
        $event = $this->createMessageEvent($response);

        $originalBalance = $this->wallet->balance;

        // Handle the event
        $this->listener->handle($event);

        // Wallet should NOT be debited (quota used instead)
        $this->wallet->refresh();
        $this->assertEquals($originalBalance, $this->wallet->balance);

        // Should not send wallet debited notification
        Notification::assertNotSentTo($this->user, WalletDebitedNotification::class);
    }

    #[Test]
    public function it_debits_wallet_when_no_subscription(): void
    {
        // Delete subscription to force wallet billing
        $this->subscription->delete();

        // Set wallet balance
        $walletBalance = 500.0;
        $this->wallet->update(['balance' => $walletBalance]);

        // Create simple response
        $response = WhatsAppMessageResponseDTO::success(
            'Simple AI response',
            new WhatsAppAIResponseDTO('Response', 'gpt-4')
        );
        $event = $this->createMessageEvent($response);

        // Expected billing amount: 1*15 (AI only) = 15 XAF
        $expectedBillingAmount = 15.0;

        // Handle the event
        $this->listener->handle($event);

        // Verify wallet was debited
        $this->wallet->refresh();
        $this->assertEquals($walletBalance - $expectedBillingAmount, $this->wallet->balance);

        // Should send wallet debited notification
        Notification::assertSentTo($this->user, WalletDebitedNotification::class);
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

    #[Test]
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

    #[Test]
    public function it_handles_missing_subscription_gracefully(): void
    {
        // Delete subscription to test wallet billing
        $this->subscription->delete();

        $response = WhatsAppMessageResponseDTO::success(
            'Simple AI response',
            new WhatsAppAIResponseDTO('Response', 'gpt-4')
        );
        $event = $this->createMessageEvent($response);

        $originalBalance = $this->wallet->balance;

        // Handle the event
        $this->listener->handle($event);

        // Wallet should be debited directly since no subscription
        $this->wallet->refresh();
        $expectedCost = 15.0; // AI message cost
        $this->assertEquals($originalBalance - $expectedCost, $this->wallet->balance);

        // Should send wallet debited notification
        Notification::assertSentTo($this->user, WalletDebitedNotification::class);
    }

    #[Test]
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
