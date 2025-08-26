<?php

declare(strict_types=1);

namespace Tests\E2E\Billing;

use App\DTOs\WhatsApp\ProductDataDTO;
use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Enums\WhatsAppStatus;
use App\Events\WhatsApp\MessageProcessedEvent;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Wallet;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppAccountUsage;
use Tests\TestCase;

abstract class BaseE2EBillingTest extends TestCase
{
    protected User $customer;
    protected Package $starterPackage;
    protected UserSubscription $subscription;
    protected WhatsAppAccount $whatsappAccount;
    protected Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createCustomerWithStarterSubscription();
        $this->createWhatsAppAccount();
    }

    /**
     * Create a real customer with starter package subscription
     */
    protected function createCustomerWithStarterSubscription(): void
    {
        // Create customer
        $this->customer = User::create([
            'first_name' => 'Test',
            'last_name' => 'Customer E2E',
            'email' => 'test.customer.e2e@example.com',
            'phone_number' => '+237987654321',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_active' => true,
            'country_id' => null, // Skip country for E2E test
            'currency' => 'XAF',
        ]);

        // Create wallet for customer
        $this->wallet = Wallet::create([
            'user_id' => $this->customer->id,
            'balance' => 1000.00, // 1000 XAF
            'currency' => 'XAF',
        ]);

        // Create starter package
        $this->starterPackage = Package::create([
            'name' => 'starter_e2e_test',
            'display_name' => 'Starter E2E Test',
            'description' => 'Package starter pour les tests E2E',
            'price' => 500.00,
            'currency' => 'XAF',
            'messages_limit' => 100,
            'context_limit' => 20,
            'accounts_limit' => 2,
            'products_limit' => 50,
            'duration_days' => 30,
            'is_recurring' => true,
            'one_time_only' => false,
            'features' => [],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Create active subscription
        $this->subscription = UserSubscription::create([
            'user_id' => $this->customer->id,
            'package_id' => $this->starterPackage->id,
            'starts_at' => now()->subDays(1),
            'ends_at' => now()->addDays(29),
            'status' => 'active',
            'messages_limit' => $this->starterPackage->messages_limit,
            'context_limit' => $this->starterPackage->context_limit,
            'accounts_limit' => $this->starterPackage->accounts_limit,
            'products_limit' => $this->starterPackage->products_limit,
        ]);
    }

    /**
     * Create WhatsApp account for the customer
     */
    protected function createWhatsAppAccount(): void
    {
        $sessionId = 'e2e_test_session_'.uniqid();

        $this->whatsappAccount = WhatsAppAccount::create([
            'user_id' => $this->customer->id,
            'session_name' => 'E2E Test Session',
            'session_id' => $sessionId,
            'phone_number' => '+237123456789',
            'status' => WhatsAppStatus::CONNECTED(),
            'qr_code' => null,
            'last_seen_at' => now(),
            'agent_name' => 'E2E Test Agent',
            'agent_enabled' => true,
            'response_time' => '5',
            'daily_ai_responses' => 0,
        ]);
    }

    /**
     * Generate realistic AI response with products
     */
    protected function generateComplexAIResponse(): WhatsAppMessageResponseDTO
    {
        $products = [
            new ProductDataDTO(
                '🍕 Pizza Margherita - Délicieuse pizza avec tomate, mozzarella et basilic frais. Prix: 3500 XAF',
                ['https://example.com/pizza1.jpg', 'https://example.com/pizza2.jpg']
            ),
            new ProductDataDTO(
                '🍔 Burger Classic - Burger juteux avec bœuf, salade, tomate et sauce spéciale. Prix: 2500 XAF',
                ['https://example.com/burger1.jpg']
            ),
            new ProductDataDTO(
                '🥤 Coca Cola - Boisson rafraîchissante 33cl. Prix: 500 XAF',
                ['https://example.com/coca1.jpg', 'https://example.com/coca2.jpg', 'https://example.com/coca3.jpg']
            ),
        ];

        return WhatsAppMessageResponseDTO::success(
            'Voici nos produits populaires ! Que souhaitez-vous commander aujourd\'hui ?',
            new WhatsAppAIResponseDTO(
                'Voici nos produits populaires ! Que souhaitez-vous commander aujourd\'hui ?',
                'gpt-4',
                0.95,
                145
            ),
            waitTime: 2,
            typingDuration: 3,
            products: $products,
            sessionId: $this->whatsappAccount->session_id,
            phoneNumber: $this->whatsappAccount->phone_number
        );
    }

    /**
     * Generate simple AI response (no products)
     */
    protected function generateSimpleAIResponse(): WhatsAppMessageResponseDTO
    {
        return WhatsAppMessageResponseDTO::success(
            'Bonjour ! Comment puis-je vous aider aujourd\'hui ?',
            new WhatsAppAIResponseDTO(
                'Bonjour ! Comment puis-je vous aider aujourd\'hui ?',
                'gpt-4',
                0.98,
                32
            ),
            waitTime: 1,
            typingDuration: 2,
            products: [],
            sessionId: $this->whatsappAccount->session_id,
            phoneNumber: $this->whatsappAccount->phone_number
        );
    }

    /**
     * Generate message request DTO
     */
    protected function generateMessageRequest(string $customerMessage = 'Bonjour'): WhatsAppMessageRequestDTO
    {
        return new WhatsAppMessageRequestDTO(
            id: 'msg_'.uniqid(),
            from: '+237987654321', // Customer phone
            body: $customerMessage,
            timestamp: now()->timestamp,
            type: 'chat',
            isGroup: false,
            chatName: null,
            metadata: []
        );
    }

    /**
     * Dispatch the message processed event
     */
    protected function dispatchMessageProcessedEvent(
        WhatsAppMessageRequestDTO $request,
        WhatsAppMessageResponseDTO $response
    ): void {
        MessageProcessedEvent::dispatch(
            $this->whatsappAccount,
            $request,
            $response
        );
    }

    /**
     * Get fresh account usage
     */
    protected function getFreshAccountUsage(): WhatsAppAccountUsage
    {
        return WhatsAppAccountUsage::where('user_subscription_id', $this->subscription->id)
            ->where('whatsapp_account_id', $this->whatsappAccount->id)
            ->first() ?? WhatsAppAccountUsage::getOrCreateForAccount($this->subscription, $this->whatsappAccount);
    }

    /**
     * Verify initial state
     */
    protected function verifyInitialState(): void
    {
        $this->assertDatabaseHas('users', [
            'id' => $this->customer->id,
            'email' => 'test.customer.e2e@example.com',
        ]);

        $this->assertDatabaseHas('packages', [
            'id' => $this->starterPackage->id,
            'name' => 'starter_e2e_test',
            'messages_limit' => 100,
        ]);

        $this->assertDatabaseHas('user_subscriptions', [
            'id' => $this->subscription->id,
            'user_id' => $this->customer->id,
            'package_id' => $this->starterPackage->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('whatsapp_accounts', [
            'id' => $this->whatsappAccount->id,
            'user_id' => $this->customer->id,
            'phone_number' => '+237123456789',
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $this->wallet->id,
            'user_id' => $this->customer->id,
            'balance' => 1000.00,
        ]);

        // Verify initial usage is 0
        $accountUsage = $this->getFreshAccountUsage();
        $this->assertEquals(0, $accountUsage->messages_used);
        $this->assertEquals(0, $accountUsage->overage_messages_used);
        $this->assertEquals(0.0, $accountUsage->overage_cost_paid_xaf);
    }

    protected function tearDown(): void
    {
        // Clean up created test data
        if (isset($this->subscription)) {
            $this->subscription->delete();
        }
        if (isset($this->starterPackage)) {
            $this->starterPackage->delete();
        }
        if (isset($this->whatsappAccount)) {
            $this->whatsappAccount->delete();
        }
        if (isset($this->wallet)) {
            $this->wallet->delete();
        }
        if (isset($this->customer)) {
            $this->customer->delete();
        }

        parent::tearDown();
    }
}
