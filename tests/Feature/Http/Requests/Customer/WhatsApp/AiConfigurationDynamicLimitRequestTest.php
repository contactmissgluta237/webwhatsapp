<?php

namespace Tests\Feature\Http\Requests\Customer\WhatsApp;

use App\Http\Requests\Customer\WhatsApp\AiConfigurationRequest;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AiConfigurationDynamicLimitRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_dynamic_limit_based_on_user_package_for_context(): void
    {
        // Create packages
        $trialPackage = Package::factory()->create([
            'name' => 'trial',
            'context_limit' => 3000,
        ]);

        $proPackage = Package::factory()->create([
            'name' => 'pro',
            'context_limit' => 7000,
        ]);

        // Create users with different packages
        $trialUser = User::factory()->create();
        UserSubscription::factory()->create([
            'user_id' => $trialUser->id,
            'package_id' => $trialPackage->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $proUser = User::factory()->create();
        UserSubscription::factory()->create([
            'user_id' => $proUser->id,
            'package_id' => $proPackage->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        // Test context with 5000 chars - should fail for trial, pass for pro
        $testContext = str_repeat('A', 5000);
        $baseData = [
            'agent_name' => 'Test Agent',
            'agent_enabled' => true,
            'agent_prompt' => 'Valid prompt', // Short valid prompt
            'contextual_information' => $testContext,
            'response_time' => 'random',
        ];

        // Trial user (3000 context limit) - should fail
        $this->actingAs($trialUser);
        $request = new AiConfigurationRequest;
        $validator = Validator::make($baseData, $request->rules());
        $this->assertTrue($validator->fails(), 'Trial user should fail validation with 5000 char context');

        // Pro user (7000 context limit) - should pass
        $this->actingAs($proUser);
        $request = new AiConfigurationRequest;
        $validator = Validator::make($baseData, $request->rules());
        $this->assertTrue($validator->passes(), 'Pro user should pass validation with 5000 char context');
    }

    public function test_agent_prompt_has_fixed_limit_for_all_users(): void
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

        // Create users with different packages
        $trialUser = User::factory()->create();
        UserSubscription::factory()->create([
            'user_id' => $trialUser->id,
            'package_id' => $trialPackage->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $businessUser = User::factory()->create();
        UserSubscription::factory()->create([
            'user_id' => $businessUser->id,
            'package_id' => $businessPackage->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        // Test agent prompt with 4000 chars - should fail for ALL users (fixed 3000 limit)
        $testPrompt = str_repeat('A', 4000);
        $baseData = [
            'agent_name' => 'Test Agent',
            'agent_enabled' => true,
            'agent_prompt' => $testPrompt,
            'response_time' => 'random',
        ];

        // Trial user - should fail (agent prompt has fixed 3000 limit)
        $this->actingAs($trialUser);
        $request = new AiConfigurationRequest;
        $validator = Validator::make($baseData, $request->rules());
        $this->assertTrue($validator->fails(), 'Trial user should fail validation with 4000 char agent prompt');

        // Business user - should also fail (agent prompt has fixed 3000 limit)
        $this->actingAs($businessUser);
        $request = new AiConfigurationRequest;
        $validator = Validator::make($baseData, $request->rules());
        $this->assertTrue($validator->fails(), 'Business user should also fail validation with 4000 char agent prompt');
    }

    public function test_uses_default_limit_without_subscription(): void
    {
        $userWithoutSubscription = User::factory()->create();
        $this->actingAs($userWithoutSubscription);

        // Test prompt exceeding default limit (3000 chars)
        $testPrompt = str_repeat('A', 3500);
        $data = [
            'agent_name' => 'Test Agent',
            'agent_enabled' => true,
            'agent_prompt' => $testPrompt,
            'response_time' => 'random',
        ];

        $request = new AiConfigurationRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails(), 'User without subscription should fail with prompt exceeding default limit');
        $this->assertStringContainsString('3', $validator->errors()->first('agent_prompt'), 'Error should mention default limit');
    }

    public function test_context_package_limit_boundaries(): void
    {
        $package = Package::factory()->create([
            'name' => 'test',
            'context_limit' => 5000,
        ]);

        $user = User::factory()->create();
        UserSubscription::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($user);

        // Test context at exact limit - should pass
        $exactLimitContext = str_repeat('A', 5000);
        $data = [
            'agent_name' => 'Test Agent',
            'agent_enabled' => true,
            'agent_prompt' => 'Valid prompt', // Short valid prompt
            'contextual_information' => $exactLimitContext,
            'response_time' => 'random',
        ];

        $request = new AiConfigurationRequest;
        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->passes(), 'Context at exact limit should pass');

        // Test context one char over limit - should fail
        $overLimitContext = str_repeat('A', 5001);
        $data['contextual_information'] = $overLimitContext;

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->fails(), 'Context one char over limit should fail');
    }
}
