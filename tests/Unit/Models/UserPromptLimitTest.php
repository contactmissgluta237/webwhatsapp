<?php

namespace Tests\Unit\Models;

use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use Mockery;
use PHPUnit\Framework\TestCase;

class UserPromptLimitTest extends TestCase
{
    // NOTE: getPromptLimit() actually returns context limit, not agent prompt limit
    // Agent prompt limit is fixed at 3000 characters
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_returns_default_context_limit_when_no_active_subscription(): void
    {
        $user = new User;

        // Mock no active subscription
        $user->setRelation('activeSubscription', null);

        $limit = $user->getPromptLimit();

        $this->assertEquals(3000, $limit, 'Should return default context limit of 3000 when no active subscription');
    }

    public function test_returns_default_context_limit_when_subscription_has_no_package(): void
    {
        $user = new User;
        $subscription = Mockery::mock(UserSubscription::class);
        $subscription->shouldReceive('getAttribute')->with('package')->andReturn(null);

        $user->setRelation('activeSubscription', $subscription);

        $limit = $user->getPromptLimit();

        $this->assertEquals(3000, $limit, 'Should return default context limit when subscription has no package');
    }

    public function test_returns_trial_package_limit(): void
    {
        $user = new User;
        $package = Mockery::mock(Package::class);
        $package->shouldReceive('getAttribute')->with('context_limit')->andReturn(3000);

        $subscription = Mockery::mock(UserSubscription::class);
        $subscription->shouldReceive('getAttribute')->with('package')->andReturn($package);

        $user->setRelation('activeSubscription', $subscription);

        $limit = $user->getPromptLimit();

        $this->assertEquals(3000, $limit, 'Should return trial package limit of 3000');
    }

    public function test_returns_starter_package_limit(): void
    {
        $user = new User;
        $package = Mockery::mock(Package::class);
        $package->shouldReceive('getAttribute')->with('context_limit')->andReturn(5000);

        $subscription = Mockery::mock(UserSubscription::class);
        $subscription->shouldReceive('getAttribute')->with('package')->andReturn($package);

        $user->setRelation('activeSubscription', $subscription);

        $limit = $user->getPromptLimit();

        $this->assertEquals(5000, $limit, 'Should return starter package limit of 5000');
    }

    public function test_returns_pro_package_limit(): void
    {
        $user = new User;
        $package = Mockery::mock(Package::class);
        $package->shouldReceive('getAttribute')->with('context_limit')->andReturn(7000);

        $subscription = Mockery::mock(UserSubscription::class);
        $subscription->shouldReceive('getAttribute')->with('package')->andReturn($package);

        $user->setRelation('activeSubscription', $subscription);

        $limit = $user->getPromptLimit();

        $this->assertEquals(7000, $limit, 'Should return pro package limit of 7000');
    }

    public function test_returns_business_package_limit(): void
    {
        $user = new User;
        $package = Mockery::mock(Package::class);
        $package->shouldReceive('getAttribute')->with('context_limit')->andReturn(10000);

        $subscription = Mockery::mock(UserSubscription::class);
        $subscription->shouldReceive('getAttribute')->with('package')->andReturn($package);

        $user->setRelation('activeSubscription', $subscription);

        $limit = $user->getPromptLimit();

        $this->assertEquals(10000, $limit, 'Should return business package limit of 10000');
    }

    public function test_returns_custom_package_limit(): void
    {
        $customLimit = 15000;
        $user = new User;
        $package = Mockery::mock(Package::class);
        $package->shouldReceive('getAttribute')->with('context_limit')->andReturn($customLimit);

        $subscription = Mockery::mock(UserSubscription::class);
        $subscription->shouldReceive('getAttribute')->with('package')->andReturn($package);

        $user->setRelation('activeSubscription', $subscription);

        $limit = $user->getPromptLimit();

        $this->assertEquals($customLimit, $limit, 'Should return custom package limit');
    }

    public function test_handles_zero_context_limit_package(): void
    {
        $user = new User;
        $package = Mockery::mock(Package::class);
        $package->shouldReceive('getAttribute')->with('context_limit')->andReturn(0);

        $subscription = Mockery::mock(UserSubscription::class);
        $subscription->shouldReceive('getAttribute')->with('package')->andReturn($package);

        $user->setRelation('activeSubscription', $subscription);

        $limit = $user->getPromptLimit();

        $this->assertEquals(0, $limit, 'Should return zero when package context_limit is 0');
    }

    public function test_package_limits_data_provider(): void
    {
        $testCases = [
            ['packageLimit' => 1000, 'expectedLimit' => 1000],
            ['packageLimit' => 2500, 'expectedLimit' => 2500],
            ['packageLimit' => 3000, 'expectedLimit' => 3000],
            ['packageLimit' => 5000, 'expectedLimit' => 5000],
            ['packageLimit' => 7000, 'expectedLimit' => 7000],
            ['packageLimit' => 10000, 'expectedLimit' => 10000],
            ['packageLimit' => 50000, 'expectedLimit' => 50000],
        ];

        foreach ($testCases as $case) {
            $user = new User;
            $package = Mockery::mock(Package::class);
            $package->shouldReceive('getAttribute')->with('context_limit')->andReturn($case['packageLimit']);

            $subscription = Mockery::mock(UserSubscription::class);
            $subscription->shouldReceive('getAttribute')->with('package')->andReturn($package);

            $user->setRelation('activeSubscription', $subscription);

            $limit = $user->getPromptLimit();

            $this->assertEquals($case['expectedLimit'], $limit,
                "Package limit {$case['packageLimit']} should return user limit {$case['expectedLimit']}");
        }
    }
}
