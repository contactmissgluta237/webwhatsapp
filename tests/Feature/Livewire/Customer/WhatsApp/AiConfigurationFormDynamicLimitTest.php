<?php

namespace Tests\Feature\Livewire\Customer\WhatsApp;

use App\Livewire\Customer\WhatsApp\AiConfigurationForm;
use App\Models\AiModel;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AiConfigurationFormDynamicLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_prompt_usage_percentage(): void
    {
        $user = User::factory()->create();
        $aiModel = AiModel::factory()->create(['is_active' => true, 'is_default' => true]);
        $account = WhatsAppAccount::factory()->create(['user_id' => $user->id]);

        $package = Package::factory()->create([
            'name' => 'test',
            'context_limit' => 1000,
        ]);

        UserSubscription::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($user);

        $component = Livewire::test(AiConfigurationForm::class, ['account' => $account]);

        // Test 50% usage (500 chars of 1000 limit)
        $prompt = str_repeat('A', 500);
        $component->set('agent_prompt', $prompt);

        $this->assertEquals(50, $component->get('promptUsagePercentage'), 'Should calculate 50% usage');
    }

    public function test_validates_prompt_with_different_package_limits(): void
    {
        $user = User::factory()->create();
        $aiModel = AiModel::factory()->create(['is_active' => true, 'is_default' => true]);
        $account = WhatsAppAccount::factory()->create(['user_id' => $user->id]);

        $smallPackage = Package::factory()->create([
            'name' => 'small',
            'context_limit' => 2000,
        ]);

        UserSubscription::factory()->create([
            'user_id' => $user->id,
            'package_id' => $smallPackage->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($user);

        $component = Livewire::test(AiConfigurationForm::class, ['account' => $account]);

        // Test prompt within limit - should not have errors
        $validPrompt = str_repeat('A', 1900);
        $component->set('agent_prompt', $validPrompt);

        $component->assertHasNoErrors('agent_prompt');

        // Test prompt exceeding limit - should have errors
        $invalidPrompt = str_repeat('A', 2200);
        $component->set('agent_prompt', $invalidPrompt);

        $component->assertHasErrors('agent_prompt');
    }

    public function test_displays_correct_user_prompt_limit(): void
    {
        $user = User::factory()->create();
        $aiModel = AiModel::factory()->create(['is_active' => true, 'is_default' => true]);
        $account = WhatsAppAccount::factory()->create(['user_id' => $user->id]);

        $businessPackage = Package::factory()->create([
            'name' => 'business',
            'context_limit' => 10000,
        ]);

        UserSubscription::factory()->create([
            'user_id' => $user->id,
            'package_id' => $businessPackage->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($user);

        $component = Livewire::test(AiConfigurationForm::class, ['account' => $account]);

        $this->assertEquals(10000, $component->get('userPromptLimit'), 'Should display business package limit');
    }

    public function test_handles_user_without_subscription(): void
    {
        $user = User::factory()->create();
        $aiModel = AiModel::factory()->create(['is_active' => true, 'is_default' => true]);
        $account = WhatsAppAccount::factory()->create(['user_id' => $user->id]);

        // No subscription created - should use default limit
        $this->actingAs($user);

        $component = Livewire::test(AiConfigurationForm::class, ['account' => $account]);

        $this->assertEquals(3000, $component->get('userPromptLimit'), 'Should use default limit without subscription');
    }
}
