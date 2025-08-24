<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\UsageSubscriptionTracker;
use App\Models\Wallet;
use App\Models\InternalTransaction;
use App\Services\WhatsApp\Helpers\MessageCostHelper;
use App\Listeners\WhatsApp\BillingCounterListener;
use App\Events\WhatsApp\MessageProcessedEvent;
use App\Models\WhatsAppAccount;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WhatsAppOverageBillingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed les packages de base seulement si pas encore fait
        if (Package::count() === 0) {
            $this->seed(\Database\Seeders\PackagesSeeder::class);
        }
    }

    #[Test]
    public function it_can_check_if_user_can_process_message_with_remaining_quota()
    {
        $user = $this->createUserWithPackage('starter', balance: 100);
        $subscription = $user->activeSubscription;
        $tracker = $subscription->getOrCreateCurrentCycleTracker();
        
        // Le user a encore 200 messages dans son quota
        $this->assertTrue(MessageCostHelper::canProcessMessage($user, 5));
        $this->assertTrue($tracker->canProcessMessage(5));
        $this->assertTrue($tracker->hasRemainingMessages());
    }

    #[Test]
    public function it_can_check_if_user_can_process_message_with_overage_when_quota_exhausted()
    {
        $user = $this->createUserWithPackage('starter', balance: 100);
        $subscription = $user->activeSubscription;
        $tracker = $subscription->getOrCreateCurrentCycleTracker();
        
        // Épuiser le quota
        $tracker->update(['messages_remaining' => 0]);
        
        $this->assertFalse($tracker->hasRemainingMessages());
        
        // Mais peut traiter grâce au wallet
        $this->assertTrue($tracker->canAffordOverage(5)); // 5 * 10 = 50 XAF < 100 XAF balance
        $this->assertTrue($tracker->canProcessMessage(5));
        $this->assertTrue(MessageCostHelper::canProcessMessage($user, 5));
    }

    #[Test]
    public function it_cannot_process_message_when_quota_exhausted_and_insufficient_wallet()
    {
        $user = $this->createUserWithPackage('starter', balance: 30); // Pas assez pour 5 messages
        $subscription = $user->activeSubscription;
        $tracker = $subscription->getOrCreateCurrentCycleTracker();
        
        // Épuiser le quota
        $tracker->update(['messages_remaining' => 0]);
        
        $this->assertFalse($tracker->hasRemainingMessages());
        $this->assertFalse($tracker->canAffordOverage(5)); // 5 * 10 = 50 XAF > 30 XAF balance
        $this->assertFalse($tracker->canProcessMessage(5));
        $this->assertFalse(MessageCostHelper::canProcessMessage($user, 5));
    }

    #[Test]
    public function it_processes_normal_quota_billing_correctly()
    {
        $user = $this->createUserWithPackage('starter', balance: 100);
        $account = WhatsAppAccount::factory()->create(['user_id' => $user->id]);
        
        // Simuler un message traité avec succès
        $event = $this->createMessageProcessedEvent($account, hasProducts: false);
        
        $tracker = $user->activeSubscription->getOrCreateCurrentCycleTracker();
        $initialRemaining = $tracker->messages_remaining;
        $initialBalance = $user->wallet->balance;
        
        $listener = new BillingCounterListener();
        $listener->handle($event);
        
        $tracker->refresh();
        $user->wallet->refresh();
        
        // Quota normal utilisé (1 message de base)
        $this->assertEquals($initialRemaining - 1, $tracker->messages_remaining);
        $this->assertEquals(1, $tracker->messages_used);
        $this->assertEquals(1, $tracker->base_messages_count);
        
        // Wallet intact
        $this->assertEquals($initialBalance, $user->wallet->balance);
        
        // Pas de dépassement
        $this->assertEquals(0, $tracker->overage_messages_used);
        $this->assertEquals(0, $tracker->overage_cost_paid_xaf);
    }

    #[Test] 
    public function it_processes_overage_billing_correctly()
    {
        $user = $this->createUserWithPackage('starter', balance: 100);
        $account = WhatsAppAccount::factory()->create(['user_id' => $user->id]);
        
        $tracker = $user->activeSubscription->getOrCreateCurrentCycleTracker();
        
        // Épuiser le quota complètement
        $tracker->update(['messages_remaining' => 0]);
        $initialBalance = $user->wallet->balance;
        
        // Simuler un message avec 2 produits (1 base + 2 médias = 3 messages total)
        $event = $this->createMessageProcessedEvent($account, hasProducts: true, productCount: 2);
        
        $listener = new BillingCounterListener();
        $listener->handle($event);
        
        $tracker->refresh();
        $user->wallet->refresh();
        
        // Quota reste à 0 (pas changé)
        $this->assertEquals(0, $tracker->messages_remaining);
        
        // Dépassement comptabilisé (3 messages)
        $this->assertEquals(3, $tracker->overage_messages_used);
        $expectedCost = 3 * 10; // 30 XAF
        $this->assertEquals($expectedCost, $tracker->overage_cost_paid_xaf);
        
        // Wallet débité
        $this->assertEquals($initialBalance - $expectedCost, $user->wallet->balance);
        
        // Transaction interne créée
        $transaction = InternalTransaction::where('wallet_id', $user->wallet->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals($expectedCost, $transaction->amount);
        $this->assertEquals('DEBIT', $transaction->transaction_type->value);
        $this->assertStringContains('Dépassement WhatsApp', $transaction->description);
        
        // Timestamps mis à jour
        $this->assertNotNull($tracker->last_message_at);
        $this->assertNotNull($tracker->last_overage_payment_at);
    }

    #[Test]
    public function it_respects_overage_disabled_configuration()
    {
        config(['pricing.overage.enabled' => false]);
        
        $user = $this->createUserWithPackage('starter', balance: 1000);
        $tracker = $user->activeSubscription->getOrCreateCurrentCycleTracker();
        
        $tracker->update(['messages_remaining' => 0]);
        
        // Même avec beaucoup d'argent, ne peut pas traiter si overage désactivé
        $this->assertFalse($tracker->canAffordOverage(5));
        $this->assertFalse($tracker->canProcessMessage(5));
    }

    private function createUserWithPackage(string $packageName, float $balance): User
    {
        $user = User::factory()->create();
        $package = Package::findByName($packageName);
        
        // Créer wallet avec balance
        Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => $balance,
        ]);
        
        // Créer subscription active
        UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);
        
        return $user->fresh(['wallet', 'activeSubscription']);
    }

    private function createMessageProcessedEvent(
        WhatsAppAccount $account, 
        bool $hasProducts = false, 
        int $productCount = 0
    ): MessageProcessedEvent {
        $request = new WhatsAppMessageRequestDTO(
            id: 'test-message-123',
            from: '1234567890',
            body: 'Test message',
            timestamp: time(),
            type: 'text',
            isGroup: false
        );
        
        $products = collect();
        if ($hasProducts) {
            for ($i = 0; $i < $productCount; $i++) {
                $products->push($this->createMockUserProduct(1)); // 1 média par produit
            }
        }
        
        $response = new WhatsAppMessageResponseDTO(
            processed: true,
            hasAiResponse: true,
            aiResponse: 'Test response',
            products: $products->toArray()
        );
        
        return new MessageProcessedEvent($account, $request, $response);
    }
    
    private function createMockUserProduct(int $mediaCount): object
    {
        $product = new class {
            public $mediaCount;
            
            public function getMediaCollection(string $collection) 
            {
                return new class($this->mediaCount) {
                    public $count;
                    
                    public function __construct($count) 
                    {
                        $this->count = $count;
                    }
                    
                    public function count() 
                    {
                        return $this->count;
                    }
                };
            }
        };
        
        $product->mediaCount = $mediaCount;
        
        return $product;
    }
}