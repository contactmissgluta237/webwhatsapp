<?php

declare(strict_types=1);

namespace Tests\Feature\Customer\WhatsApp;

use App\Livewire\Customer\WhatsApp\AiConfigurationForm;
use App\Models\AiModel;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Wallet;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class AiConfigurationFormTest extends TestCase
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

    public function test_can_load_configuration_form(): void
    {
        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->assertStatus(200)
            ->assertSet('agent_enabled', false)
            ->assertSet('ai_model_id', $this->aiModel->id);
    }

    public function test_cannot_enable_agent_without_subscription_and_wallet(): void
    {
        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_enabled', true)
            ->call('save')
            ->assertDispatched('configuration-saved');

        $this->assertFalse($this->account->fresh()->agent_enabled);
    }

    public function test_cannot_enable_agent_with_insufficient_wallet_balance(): void
    {
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 10, // Less than required 15 XAF
        ]);

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_enabled', true)
            ->call('save')
            ->assertDispatched('configuration-saved');

        $this->assertFalse($this->account->fresh()->agent_enabled);
    }

    public function test_can_enable_agent_with_sufficient_wallet_balance(): void
    {
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 1000, // More than required 15 XAF
        ]);

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_enabled', true)
            ->set('agent_name', 'Test Agent')
            ->set('agent_prompt', 'Test prompt for the agent')
            ->call('save')
            ->assertDispatched('configuration-saved');

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

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_enabled', true)
            ->call('save')
            ->assertDispatched('configuration-saved');

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

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_enabled', true)
            ->set('agent_name', 'Subscription Agent')
            ->set('agent_prompt', 'Test prompt for subscription agent')
            ->call('save')
            ->assertDispatched('configuration-saved');

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

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_enabled', true)
            ->call('save')
            ->assertDispatched('configuration-saved');

        $this->assertFalse($this->account->fresh()->agent_enabled);
    }

    public function test_can_disable_agent(): void
    {
        $this->account->update([
            'agent_enabled' => true,
            'ai_model_id' => $this->aiModel->id,
        ]);

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->assertSet('agent_enabled', true)
            ->set('agent_enabled', false)
            ->call('save')
            ->assertDispatched('configuration-saved');

        $this->assertFalse($this->account->fresh()->agent_enabled);
    }

    public function test_can_update_configuration_without_changing_agent_status(): void
    {
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 1000,
        ]);

        $this->account->update([
            'agent_enabled' => true,
            'ai_model_id' => $this->aiModel->id,
        ]);

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_name', 'Updated Agent Name')
            ->set('agent_prompt', 'Updated prompt content')
            ->set('contextual_information', 'New contextual info')
            ->call('save')
            ->assertDispatched('configuration-saved');

        $account = $this->account->fresh();
        $this->assertTrue($account->agent_enabled);
        $this->assertEquals('Updated Agent Name', $account->session_name);
        $this->assertEquals('Updated prompt content', $account->agent_prompt);
        $this->assertEquals('New contextual info', $account->contextual_information);
    }

    public function test_validates_prompt_length(): void
    {
        $longPrompt = str_repeat('a', 10001); // Max length is 10000

        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_prompt', $longPrompt)
            ->call('save')
            ->assertHasErrors('agent_prompt');
    }

    public function test_cannot_enable_agent_when_ai_model_is_invalid(): void
    {
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 1000,
        ]);

        // Try to enable agent with a non-existent model ID
        Livewire::test(AiConfigurationForm::class, ['account' => $this->account])
            ->set('agent_enabled', true)
            ->set('ai_model_id', 999999) // Non-existent model ID
            ->call('save')
            ->assertHasErrors('ai_model_id');

        // The agent should not be enabled since the AI model doesn't exist
        $this->assertFalse($this->account->fresh()->agent_enabled);
    }
}
