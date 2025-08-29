<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WhatsAppMessageProcessingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WhatsAppAccount $account;
    private UserSubscription $subscription;
    private Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed packages
        $this->artisan('db:seed', ['--class' => 'PackagesSeeder']);

        $this->package = Package::where('name', 'starter')->first();

        $this->user = User::factory()->create();
        $this->account = WhatsAppAccount::factory()->create(['user_id' => $this->user->id]);

        // Créer un wallet pour l'utilisateur
        $this->user->wallet()->create([
            'balance' => 0,
            'currency' => 'XAF',
        ]);
    }

    public static function subscriptionStatesProvider(): array
    {
        return [
            'no_messages_no_wallet_active_subscription' => [
                'messagesUsed' => 200, // Limite du package starter
                'walletBalance' => 0,
                'subscriptionActive' => true,
                'shouldProcess' => false,
                'expectedDescription' => 'Ne traite pas: pas de messages restants et wallet vide',
            ],
            'no_messages_with_wallet_active_subscription' => [
                'messagesUsed' => 200, // Limite atteinte
                'walletBalance' => 1000, // Wallet suffisant
                'subscriptionActive' => true,
                'shouldProcess' => true,
                'expectedDescription' => 'Traite avec overage: wallet débité',
            ],
            'no_messages_with_wallet_expired_subscription' => [
                'messagesUsed' => 200,
                'walletBalance' => 1000,
                'subscriptionActive' => false,
                'shouldProcess' => false,
                'expectedDescription' => 'Ne traite pas: souscription expirée',
            ],
            'no_messages_no_wallet_expired_subscription' => [
                'messagesUsed' => 200,
                'walletBalance' => 0,
                'subscriptionActive' => false,
                'shouldProcess' => false,
                'expectedDescription' => 'Ne traite pas: souscription expirée et wallet vide',
            ],
            'has_messages_active_subscription' => [
                'messagesUsed' => 100, // Moins que la limite
                'walletBalance' => 0,
                'subscriptionActive' => true,
                'shouldProcess' => true,
                'expectedDescription' => 'Traite normalement: messages restants',
            ],
        ];
    }

    /**
     * @dataProvider subscriptionStatesProvider
     */
    public function test_message_processing_logic(
        int $messagesUsed,
        float $walletBalance,
        bool $subscriptionActive,
        bool $shouldProcess,
        string $expectedDescription
    ): void {
        // Arrange
        $this->setupSubscription($subscriptionActive, $messagesUsed);
        $this->user->wallet->update(['balance' => $walletBalance]);

        $messageCost = 1;
        $products = collect();

        // Mock event
        $event = $this->createMockEvent($shouldProcess);

        // Act & Assert
        if ($shouldProcess) {
            $this->assertMessageShouldBeProcessed($event, $messageCost, $expectedDescription);
        } else {
            $this->assertMessageShouldNotBeProcessed($messageCost, $expectedDescription);
        }
    }

    public function test_overage_billing_wallet_debit(): void
    {
        // Arrange: Package limit atteint, wallet suffisant
        $this->setupSubscription(true, 200); // Limite atteinte
        $initialBalance = 1000.0;
        $this->user->wallet->update(['balance' => $initialBalance]);

        $messageCost = 1;
        $products = collect();
        $expectedDebit = $messageCost * config('pricing.overage.cost_per_message_xaf', 10);

        // Mock successful event
        $event = $this->createMockEvent(true);

        // Act
        $listener = app(StoreMessagesListener::class);
        $listener->handle($event);

        // Assert
        $this->user->wallet->refresh();
        $this->assertEquals($initialBalance - $expectedDebit, $this->user->wallet->balance);

        $accountUsage = WhatsAppAccountUsage::where([
            'user_subscription_id' => $this->subscription->id,
            'whatsapp_account_id' => $this->account->id,
        ])->first();

        $this->assertNotNull($accountUsage);
        $this->assertEquals(1, $accountUsage->overage_messages_used);
        $this->assertEquals($expectedDebit, $accountUsage->overage_cost_paid_xaf);
    }

    public function test_normal_quota_usage(): void
    {
        // Arrange: Messages restants disponibles (ne pas pré-créer d'usage)
        $this->setupSubscription(true, 0); // 0 messages utilisés = tous disponibles

        $messageCost = 1;
        $event = $this->createMockEvent(true);

        // Act
        $listener = app(StoreMessagesListener::class);
        $listener->handle($event);

        // Assert
        $accountUsage = WhatsAppAccountUsage::where([
            'user_subscription_id' => $this->subscription->id,
            'whatsapp_account_id' => $this->account->id,
        ])->first();

        $this->assertNotNull($accountUsage);
        $this->assertEquals(1, $accountUsage->messages_used);
        $this->assertEquals(0, $accountUsage->overage_messages_used);
        $this->assertEquals($this->package->messages_limit - 1, $this->subscription->getRemainingMessages());
    }

    private function setupSubscription(bool $active, int $messagesUsed): void
    {
        $this->subscription = UserSubscription::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'starts_at' => now()->subDays(10),
            'ends_at' => $active ? now()->addDays(20) : now()->subDays(1),
            'status' => $active ? 'active' : 'expired',
            'messages_limit' => $this->package->messages_limit,
            'context_limit' => $this->package->context_limit,
            'accounts_limit' => $this->package->accounts_limit,
            'products_limit' => $this->package->products_limit,
            'activated_at' => now()->subDays(10),
        ]);

        // Créer l'usage avec les messages déjà utilisés
        if ($messagesUsed > 0) {
            WhatsAppAccountUsage::create([
                'user_subscription_id' => $this->subscription->id,
                'whatsapp_account_id' => $this->account->id,
                'messages_used' => $messagesUsed,
                'base_messages_count' => $messagesUsed,
                'media_messages_count' => 0,
                'overage_messages_used' => 0,
                'overage_cost_paid_xaf' => 0,
                'estimated_cost_xaf' => $messagesUsed * 10,
                'last_message_at' => now()->subHour(),
            ]);
        }
    }

    private function createMockEvent(bool $successful): MessageProcessedEvent
    {
        // Créer les DTOs requis
        $requestDTO = new WhatsAppMessageRequestDTO(
            id: 'test-msg-123',
            from: '237123456789',
            body: 'Hello test',
            timestamp: time(),
            type: 'text',
            isGroup: false,
            chatName: null,
            metadata: []
        );

        $responseDTO = new WhatsAppMessageResponseDTO(
            processed: $successful,
            hasAiResponse: $successful,
            aiResponse: $successful ? 'Test response' : null,
            processingError: $successful ? null : 'Test error',
            products: []
        );

        return new MessageProcessedEvent(
            $this->account,
            $requestDTO,
            $responseDTO
        );
    }

    private function assertMessageShouldBeProcessed(
        MessageProcessedEvent $event,
        int $messageCost,
        string $description
    ): void {
        $subscription = $this->user->activeSubscription;
        $canProcess = ($subscription && $subscription->getRemainingMessages() >= $messageCost) ||
                     ($this->user->wallet && $this->user->wallet->balance >= $messageCost);
        $this->assertTrue($canProcess, "Should process message: {$description}");

        // Test que le listener traite le message sans erreur
        $listener = app(StoreMessagesListener::class);
        $listener->handle($event);

        // Vérifier qu'une usage entry a été créée ou mise à jour
        $accountUsage = WhatsAppAccountUsage::where([
            'user_subscription_id' => $this->subscription->id,
            'whatsapp_account_id' => $this->account->id,
        ])->first();

        $this->assertNotNull($accountUsage, "Usage should be tracked: {$description}");
    }

    private function assertMessageShouldNotBeProcessed(
        int $messageCost,
        string $description
    ): void {
        $subscription = $this->user->activeSubscription;
        $canProcess = ($subscription && $subscription->getRemainingMessages() >= $messageCost) ||
                     ($this->user->wallet && $this->user->wallet->balance >= $messageCost);
        $this->assertFalse($canProcess, "Should NOT process message: {$description}");
    }
}
