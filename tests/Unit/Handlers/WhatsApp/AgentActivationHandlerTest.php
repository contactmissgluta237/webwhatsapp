<?php

declare(strict_types=1);

namespace Tests\Unit\Handlers\WhatsApp;

use App\Handlers\WhatsApp\AgentActivationHandler;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Wallet;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AgentActivationHandlerTest extends TestCase
{
    use RefreshDatabase;

    private AgentActivationHandler $handler;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new AgentActivationHandler;
        $this->user = User::factory()->create();
    }

    public function test_denies_activation_when_no_subscription_and_no_wallet(): void
    {
        $result = $this->handler->handle($this->user);

        $this->assertFalse($result->canActivate);
        $this->assertEquals(__('Insufficient balance. Minimum required: :amount :currency to activate an agent', [
            'amount' => 15,
            'currency' => 'XAF',
        ]), $result->reason);
        $this->assertEquals(0, $result->maxAllowedAgents);
        $this->assertFalse($result->hasActiveSubscription);
    }

    public function test_allows_one_agent_when_wallet_has_balance(): void
    {
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 1000,
        ]);

        $result = $this->handler->handle($this->user);

        $this->assertTrue($result->canActivate);
        $this->assertEquals(1, $result->maxAllowedAgents);
        $this->assertFalse($result->hasActiveSubscription);
        $this->assertEquals(1000.0, $result->walletBalance);
    }

    public function test_denies_second_agent_with_wallet_only(): void
    {
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 1000,
        ]);

        WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'agent_enabled' => true,
        ]);

        $result = $this->handler->handle($this->user);

        $this->assertFalse($result->canActivate);
        $this->assertEquals(__('Without active subscription, you can only activate one agent at a time'), $result->reason);
        $this->assertEquals(1, $result->currentActiveAgents);
        $this->assertEquals(1, $result->maxAllowedAgents);
    }

    public function test_allows_activation_with_active_subscription(): void
    {
        $package = Package::factory()->create(['accounts_limit' => 3]);

        UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'accounts_limit' => $package->accounts_limit,
        ]);

        $result = $this->handler->handle($this->user);

        $this->assertTrue($result->canActivate);
        $this->assertEquals(3, $result->maxAllowedAgents);
        $this->assertTrue($result->hasActiveSubscription);
    }

    public function test_denies_when_subscription_limit_reached(): void
    {
        $package = Package::factory()->create(['accounts_limit' => 2]);

        UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'accounts_limit' => $package->accounts_limit,
        ]);

        WhatsAppAccount::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'agent_enabled' => true,
        ]);

        $result = $this->handler->handle($this->user);

        $this->assertFalse($result->canActivate);
        $this->assertEquals(__('Your package limit reached: :current/:max active agents', [
            'current' => 2,
            'max' => 2,
        ]), $result->reason);
        $this->assertEquals(2, $result->currentActiveAgents);
        $this->assertEquals(2, $result->maxAllowedAgents);
    }

    public function test_prioritizes_subscription_over_wallet(): void
    {
        $package = Package::factory()->create(['accounts_limit' => 5]);

        UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
            'accounts_limit' => $package->accounts_limit,
        ]);

        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 500,
        ]);

        $result = $this->handler->handle($this->user);

        $this->assertTrue($result->canActivate);
        $this->assertEquals(5, $result->maxAllowedAgents);
        $this->assertTrue($result->hasActiveSubscription);
    }
}
