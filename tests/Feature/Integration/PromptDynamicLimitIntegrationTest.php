<?php

namespace Tests\Feature\Integration;

use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromptDynamicLimitIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test complete flow: Package -> User -> Subscription -> Limit -> Validation
     */
    public function test_complete_dynamic_limit_flow(): void
    {
        // Create packages with different limits
        $trialPackage = Package::factory()->create([
            'name' => 'trial',
            'context_limit' => 3000,
        ]);

        $businessPackage = Package::factory()->create([
            'name' => 'business',
            'context_limit' => 10000,
        ]);

        // Create users
        $trialUser = User::factory()->create();
        $businessUser = User::factory()->create();

        // Create subscriptions
        UserSubscription::factory()->create([
            'user_id' => $trialUser->id,
            'package_id' => $trialPackage->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        UserSubscription::factory()->create([
            'user_id' => $businessUser->id,
            'package_id' => $businessPackage->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        // Test limits are correctly applied
        $this->assertEquals(3000, $trialUser->getPromptLimit(), 'Trial user should have 3000 limit');
        $this->assertEquals(10000, $businessUser->getPromptLimit(), 'Business user should have 10000 limit');
    }

    public function test_user_without_subscription_uses_default(): void
    {
        $user = User::factory()->create();
        // No subscription created

        $this->assertEquals(3000, $user->getPromptLimit(), 'User without subscription should use default limit');
    }

    public function test_expired_subscription_uses_default(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create([
            'name' => 'expired',
            'context_limit' => 8000,
        ]);

        // Create expired subscription
        UserSubscription::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'status' => 'expired',
            'starts_at' => now()->subMonth(2),
            'ends_at' => now()->subMonth(),
        ]);

        $this->assertEquals(3000, $user->getPromptLimit(), 'User with expired subscription should use default limit');
    }

    public function test_package_limit_updates_affect_users(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create([
            'name' => 'dynamic',
            'context_limit' => 5000,
        ]);

        UserSubscription::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        // Initial limit
        $this->assertEquals(5000, $user->getPromptLimit());

        // Update package limit
        $package->update(['context_limit' => 12000]);

        // User should see new limit immediately
        $user = $user->fresh();
        $this->assertEquals(12000, $user->getPromptLimit(), 'Updated package limit should be reflected immediately');
    }
}
