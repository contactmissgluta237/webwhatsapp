<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Models\AiModel;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Wallet;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ToggleAiControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WhatsAppAccount $account;
    private AiModel $aiModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->aiModel = AiModel::factory()->create(['is_default' => true]);
        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'agent_enabled' => false,
        ]);

        $this->actingAs($this->user);
    }

    public function test_cannot_enable_agent_without_subscription_and_wallet(): void
    {
        $response = $this->post(route('whatsapp.toggle-ai', $this->account), [
            'enable' => true,
        ]);

        $response->assertRedirect(route('whatsapp.index'));
        $response->assertSessionHas('error', __('Insufficient balance. Minimum required: :amount :currency to activate an agent', [
            'amount' => config('whatsapp.billing.costs.ai_message', 15),
            'currency' => config('app.currency', 'XAF'),
        ]));

        $this->assertFalse($this->account->fresh()->agent_enabled);
    }

    public function test_cannot_enable_agent_with_insufficient_wallet_balance(): void
    {
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 10, // Less than required 15 XAF
        ]);

        $response = $this->post(route('whatsapp.toggle-ai', $this->account), [
            'enable' => true,
        ]);

        $response->assertRedirect(route('whatsapp.index'));
        $response->assertSessionHas('error', __('Insufficient balance. Minimum required: :amount :currency to activate an agent', [
            'amount' => 15,
            'currency' => 'XAF',
        ]));

        $this->assertFalse($this->account->fresh()->agent_enabled);
    }

    public function test_can_enable_agent_with_sufficient_wallet_balance(): void
    {
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 1000, // More than required 15 XAF
        ]);

        $response = $this->post(route('whatsapp.toggle-ai', $this->account), [
            'enable' => true,
        ]);

        $response->assertRedirect(route('whatsapp.index'));
        $response->assertSessionHas('success', 'Agent enabled successfully');

        $account = $this->account->fresh();
        $this->assertTrue($account->agent_enabled);
        $this->assertEquals($this->aiModel->id, $account->ai_model_id);
    }

    public function test_cannot_enable_second_agent_with_wallet_only(): void
    {
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 1000,
        ]);

        WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'agent_enabled' => true,
        ]);

        $response = $this->post(route('whatsapp.toggle-ai', $this->account), [
            'enable' => true,
        ]);

        $response->assertRedirect(route('whatsapp.index'));
        $response->assertSessionHas('error', __('Without active subscription, you can only activate one agent at a time'));

        $this->assertFalse($this->account->fresh()->agent_enabled);
    }

    public function test_can_enable_agent_with_active_subscription(): void
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

        $response = $this->post(route('whatsapp.toggle-ai', $this->account), [
            'enable' => true,
        ]);

        $response->assertRedirect(route('whatsapp.index'));
        $response->assertSessionHas('success', 'Agent enabled successfully');

        $this->assertTrue($this->account->fresh()->agent_enabled);
    }

    public function test_cannot_enable_agent_when_package_limit_reached(): void
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

        $response = $this->post(route('whatsapp.toggle-ai', $this->account), [
            'enable' => true,
        ]);

        $response->assertRedirect(route('whatsapp.index'));
        $response->assertSessionHas('error');

        $this->assertFalse($this->account->fresh()->agent_enabled);
    }

    public function test_can_disable_agent(): void
    {
        $this->account->update([
            'agent_enabled' => true,
            'ai_model_id' => $this->aiModel->id,
        ]);

        $response = $this->post(route('whatsapp.toggle-ai', $this->account), [
            'enable' => false,
        ]);

        $response->assertRedirect(route('whatsapp.index'));
        $response->assertSessionHas('success', 'Agent disabled successfully');

        $this->assertFalse($this->account->fresh()->agent_enabled);
    }

    public function test_cannot_toggle_other_users_account(): void
    {
        $otherUser = User::factory()->create();
        $otherAccount = WhatsAppAccount::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->post(route('whatsapp.toggle-ai', $otherAccount), [
            'enable' => true,
        ]);

        $response->assertRedirect();
    }

    public function test_shows_error_when_no_ai_model_available(): void
    {
        $this->aiModel->delete();

        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 1000,
        ]);

        $response = $this->post(route('whatsapp.toggle-ai', $this->account), [
            'enable' => true,
        ]);

        $response->assertRedirect(route('whatsapp.index'));
        $response->assertSessionHas('error', 'No AI model available');

        $this->assertFalse($this->account->fresh()->agent_enabled);
    }
}
