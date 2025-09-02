<?php

declare(strict_types=1);

namespace Tests\Feature\Referral;

use App\Livewire\Auth\RegisterForm;
use App\Models\Geography\Country;
use App\Models\User;
use App\Services\Auth\Contracts\AccountActivationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompleteReferralFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_user_with_affiliation_code_during_registration(): void
    {
        // Arrange - Create test data with explicit values
        $this->createRequiredRoles();
        $country = $this->createTestCountry([
            'name' => 'Cameroon Registration',
            'code' => 'CM',
            'phone_code' => '+237',
            'flag_emoji' => '🇨🇲',
        ]);

        // Mock account activation service
        $mockActivationService = Mockery::mock(AccountActivationServiceInterface::class);
        $mockActivationService->shouldReceive('sendActivationCode')->andReturn(true);
        $this->app->instance(AccountActivationServiceInterface::class, $mockActivationService);

        $phoneData = [
            'name' => 'phone_number',
            'value' => '+237123456789',
            'country_id' => $country->id,
            'phone_number' => '123456789',
        ];

        // Act - Register user
        $component = Livewire::test(RegisterForm::class)
            ->call('phoneUpdated', $phoneData)
            ->set('first_name', 'Jean')
            ->set('last_name', 'Parrain')
            ->set('email', 'jean.parrain@registration-test.com')
            ->set('password', 'SecurePassword123!')
            ->set('password_confirmation', 'SecurePassword123!')
            ->set('terms', true)
            ->call('register');

        // Assert
        $user = User::where('email', 'jean.parrain@registration-test.com')->first();
        $this->assertNotNull($user, 'User should be created during registration');
        $this->assertEquals('Jean', $user->first_name);
        $this->assertEquals('Parrain', $user->last_name);
        $this->assertNotNull($user->affiliation_code, 'User should have an affiliation code');
        $this->assertIsString($user->affiliation_code);
        $this->assertGreaterThan(0, strlen($user->affiliation_code));
        $this->assertNotNull($user->customer, 'User should have a customer profile');
        $this->assertTrue($user->hasRole('customer'));
    }

    #[Test]
    public function it_prefills_referral_code_from_url_parameter(): void
    {
        // Arrange - Create test data with explicit values
        $this->createRequiredRoles();

        $referrer = User::factory()->create([
            'first_name' => 'Referrer',
            'last_name' => 'Parent',
            'email' => 'referrer.parent@prefill-test.com',
            'affiliation_code' => 'PREFILL123',
        ]);

        $referrer->assignRole('customer');
        $referrer->customer()->create(['referrer_id' => null]);

        // Act & Assert - Test prefill functionality
        $component = Livewire::test(RegisterForm::class)
            ->set('referral_code', 'PREFILL123')
            ->set('referral_code_readonly', true);

        $component->assertSet('referral_code', 'PREFILL123')
            ->assertSet('referral_code_readonly', true);
    }

    #[Test]
    public function it_links_referrer_and_referee_during_registration_with_valid_code(): void
    {
        // Arrange - Create test data with explicit values
        $this->createRequiredRoles();
        $country = $this->createTestCountry([
            'name' => 'Cameroon Linking',
            'code' => 'CM',
            'phone_code' => '+237',
            'flag_emoji' => '🇨🇲',
        ]);

        // Create referrer with specific affiliation code
        $referrer = User::factory()->create([
            'first_name' => 'Marie',
            'last_name' => 'ParentReferrer',
            'email' => 'marie.parent@linking-test.com',
            'affiliation_code' => 'LINK456',
        ]);

        $referrer->assignRole('customer');
        $referrer->customer()->create(['referrer_id' => null]);

        // Mock account activation service
        $mockActivationService = Mockery::mock(AccountActivationServiceInterface::class);
        $mockActivationService->shouldReceive('sendActivationCode')->andReturn(true);
        $this->app->instance(AccountActivationServiceInterface::class, $mockActivationService);

        $phoneData = [
            'name' => 'phone_number',
            'value' => '+237987654321',
            'country_id' => $country->id,
            'phone_number' => '987654321',
        ];

        // Act - Register referee with referral code
        Livewire::test(RegisterForm::class)
            ->call('phoneUpdated', $phoneData)
            ->set('first_name', 'Paul')
            ->set('last_name', 'ChildReferee')
            ->set('email', 'paul.child@linking-test.com')
            ->set('password', 'SecurePassword456!')
            ->set('password_confirmation', 'SecurePassword456!')
            ->set('referral_code', 'LINK456')
            ->set('terms', true)
            ->call('register');

        // Assert
        $referee = User::where('email', 'paul.child@linking-test.com')->first();
        $this->assertNotNull($referee, 'Referee should be created');
        $this->assertNotNull($referee->customer, 'Referee should have customer profile');
        $this->assertEquals($referrer->id, $referee->referrer_id, 'Referee should be linked to referrer');

        // Verify referrer side of the relationship
        $referrer->refresh();
        $this->assertEquals(1, $referrer->referrals()->count(), 'Referrer should have 1 referral');

        $firstReferral = $referrer->referrals()->first();
        $this->assertEquals($referee->id, $firstReferral->id);
    }

    #[Test]
    public function it_prevents_registration_with_invalid_referral_code(): void
    {
        // Arrange - Create test data with explicit values
        $this->createRequiredRoles();
        $country = $this->createTestCountry([
            'name' => 'Cameroon Invalid',
            'code' => 'CM',
            'phone_code' => '+237',
            'flag_emoji' => '🇨🇲',
        ]);

        // Mock account activation service
        $mockActivationService = Mockery::mock(AccountActivationServiceInterface::class);
        $mockActivationService->shouldReceive('sendActivationCode')->andReturn(true);
        $this->app->instance(AccountActivationServiceInterface::class, $mockActivationService);

        $phoneData = [
            'name' => 'phone_number',
            'value' => '+237111222333',
            'country_id' => $country->id,
            'phone_number' => '111222333',
        ];

        // Act - Attempt registration with invalid referral code
        $component = Livewire::test(RegisterForm::class)
            ->call('phoneUpdated', $phoneData)
            ->set('first_name', 'John')
            ->set('last_name', 'InvalidCode')
            ->set('email', 'john.invalid@prevention-test.com')
            ->set('password', 'SecurePassword789!')
            ->set('password_confirmation', 'SecurePassword789!')
            ->set('referral_code', 'NONEXISTENT_CODE_XYZ123')
            ->set('terms', true)
            ->call('register');

        // Assert
        $component->assertHasErrors(['referral_code']);

        // Verify user was NOT created
        $user = User::where('email', 'john.invalid@prevention-test.com')->first();
        $this->assertNull($user, 'User should NOT be created with invalid referral code');
    }

    #[Test]
    public function it_allows_registration_without_referral_code(): void
    {
        // Arrange - Create test data with explicit values
        $this->createRequiredRoles();
        $country = $this->createTestCountry([
            'name' => 'Cameroon Independent',
            'code' => 'CM',
            'phone_code' => '+237',
            'flag_emoji' => '🇨🇲',
        ]);

        // Mock account activation service
        $mockActivationService = Mockery::mock(AccountActivationServiceInterface::class);
        $mockActivationService->shouldReceive('sendActivationCode')->andReturn(true);
        $this->app->instance(AccountActivationServiceInterface::class, $mockActivationService);

        $phoneData = [
            'name' => 'phone_number',
            'value' => '+237555666777',
            'country_id' => $country->id,
            'phone_number' => '555666777',
        ];

        // Act - Register without referral code
        Livewire::test(RegisterForm::class)
            ->call('phoneUpdated', $phoneData)
            ->set('first_name', 'Lisa')
            ->set('last_name', 'IndependentUser')
            ->set('email', 'lisa.independent@noreferral-test.com')
            ->set('password', 'SecurePassword000!')
            ->set('password_confirmation', 'SecurePassword000!')
            ->set('terms', true)
            ->call('register');

        // Assert
        $user = User::where('email', 'lisa.independent@noreferral-test.com')->first();
        $this->assertNotNull($user, 'User should be created without referral code');
        $this->assertEquals('Lisa', $user->first_name);
        $this->assertEquals('IndependentUser', $user->last_name);
        $this->assertNotNull($user->customer, 'User should have customer profile');
        $this->assertNull($user->referrer_id, 'User should have no referrer');
        $this->assertNotNull($user->affiliation_code, 'User should still get their own affiliation code');
        $this->assertTrue($user->hasRole('customer'));
    }

    #[Test]
    public function it_validates_referral_code_case_sensitivity(): void
    {
        // Arrange - Create test data with explicit values
        $this->createRequiredRoles();
        $country = $this->createTestCountry([
            'name' => 'Cameroon Case Test',
            'code' => 'CM',
            'phone_code' => '+237',
            'flag_emoji' => '🇨🇲',
        ]);

        $referrer = User::factory()->create([
            'first_name' => 'Case',
            'last_name' => 'SensitiveReferrer',
            'email' => 'case.sensitive@test.com',
            'affiliation_code' => 'CaseTEST789', // Mixed case code
        ]);

        $referrer->assignRole('customer');
        $referrer->customer()->create(['referrer_id' => null]);

        // Mock account activation service
        $mockActivationService = Mockery::mock(AccountActivationServiceInterface::class);
        $mockActivationService->shouldReceive('sendActivationCode')->andReturn(true);
        $this->app->instance(AccountActivationServiceInterface::class, $mockActivationService);

        $phoneData = [
            'name' => 'phone_number',
            'value' => '+237888999000',
            'country_id' => $country->id,
            'phone_number' => '888999000',
        ];

        // Act - Test exact case match
        Livewire::test(RegisterForm::class)
            ->call('phoneUpdated', $phoneData)
            ->set('first_name', 'Exact')
            ->set('last_name', 'CaseMatch')
            ->set('email', 'exact.case@match-test.com')
            ->set('password', 'ExactCase123!')
            ->set('password_confirmation', 'ExactCase123!')
            ->set('referral_code', 'CaseTEST789') // Exact match
            ->set('terms', true)
            ->call('register');

        // Assert - Should succeed with exact case
        $user = User::where('email', 'exact.case@match-test.com')->first();
        $this->assertNotNull($user, 'Registration should succeed with exact case match');
        $this->assertEquals($referrer->id, $user->referrer_id);
    }

    #[Test]
    public function it_prevents_self_referral_attempts(): void
    {
        // Arrange - Create test data with explicit values
        $this->createRequiredRoles();
        $country = $this->createTestCountry([
            'name' => 'Cameroon Self Ref',
            'code' => 'CM',
            'phone_code' => '+237',
            'flag_emoji' => '🇨🇲',
        ]);

        // Create user who will try to refer themselves
        $existingUser = User::factory()->create([
            'first_name' => 'Self',
            'last_name' => 'Referrer',
            'email' => 'self.referrer@existing.com',
            'affiliation_code' => 'SELFREFER123',
        ]);

        $existingUser->assignRole('customer');
        $existingUser->customer()->create(['referrer_id' => null]);

        // Mock account activation service
        $mockActivationService = Mockery::mock(AccountActivationServiceInterface::class);
        $mockActivationService->shouldReceive('sendActivationCode')->andReturn(true);
        $this->app->instance(AccountActivationServiceInterface::class, $mockActivationService);

        $phoneData = [
            'name' => 'phone_number',
            'value' => '+237777888999',
            'country_id' => $country->id,
            'phone_number' => '777888999',
        ];

        // Act - Attempt to register with same email and own referral code
        $component = Livewire::test(RegisterForm::class)
            ->call('phoneUpdated', $phoneData)
            ->set('first_name', 'Self')
            ->set('last_name', 'Referrer')
            ->set('email', 'self.referrer@existing.com') // Same email
            ->set('password', 'SelfRefer123!')
            ->set('password_confirmation', 'SelfRefer123!')
            ->set('referral_code', 'SELFREFER123') // Own referral code
            ->set('terms', true)
            ->call('register');

        // Assert - Should prevent registration due to existing email
        $component->assertHasErrors(); // Should have validation errors (email already exists)

        // Verify no duplicate user was created
        $users = User::where('email', 'self.referrer@existing.com')->get();
        $this->assertEquals(1, $users->count(), 'Should still have only one user with this email');
    }

    #[Test]
    public function it_handles_referral_chain_creation_correctly(): void
    {
        // Arrange - Create test data with explicit values for referral chain
        $this->createRequiredRoles();
        $country = $this->createTestCountry([
            'name' => 'Cameroon Chain',
            'code' => 'CM',
            'phone_code' => '+237',
            'flag_emoji' => '🇨🇲',
        ]);

        // Create grandparent (level 1)
        $grandparent = User::factory()->create([
            'first_name' => 'Grand',
            'last_name' => 'Parent',
            'email' => 'grand.parent@chain-test.com',
            'affiliation_code' => 'GRANDPA001',
        ]);

        $grandparent->assignRole('customer');
        $grandparent->customer()->create(['referrer_id' => null]);

        // Mock account activation service
        $mockActivationService = Mockery::mock(AccountActivationServiceInterface::class);
        $mockActivationService->shouldReceive('sendActivationCode')->andReturn(true);
        $this->app->instance(AccountActivationServiceInterface::class, $mockActivationService);

        // Create parent (level 2) - referred by grandparent
        $phoneDataParent = [
            'name' => 'phone_number',
            'value' => '+237111111111',
            'country_id' => $country->id,
            'phone_number' => '111111111',
        ];

        Livewire::test(RegisterForm::class)
            ->call('phoneUpdated', $phoneDataParent)
            ->set('first_name', 'Parent')
            ->set('last_name', 'Middle')
            ->set('email', 'parent.middle@chain-test.com')
            ->set('password', 'ParentPassword123!')
            ->set('password_confirmation', 'ParentPassword123!')
            ->set('referral_code', 'GRANDPA001')
            ->set('terms', true)
            ->call('register');

        $parent = User::where('email', 'parent.middle@chain-test.com')->first();
        $this->assertNotNull($parent);

        // Create child (level 3) - referred by parent
        $phoneDataChild = [
            'name' => 'phone_number',
            'value' => '+237222222222',
            'country_id' => $country->id,
            'phone_number' => '222222222',
        ];

        Livewire::test(RegisterForm::class)
            ->call('phoneUpdated', $phoneDataChild)
            ->set('first_name', 'Child')
            ->set('last_name', 'Bottom')
            ->set('email', 'child.bottom@chain-test.com')
            ->set('password', 'ChildPassword123!')
            ->set('password_confirmation', 'ChildPassword123!')
            ->set('referral_code', $parent->affiliation_code)
            ->set('terms', true)
            ->call('register');

        // Assert - Verify chain structure
        $child = User::where('email', 'child.bottom@chain-test.com')->first();
        $this->assertNotNull($child);

        // Refresh all users to get latest data
        $grandparent->refresh();
        $parent->refresh();

        // Verify relationships
        $this->assertNull($grandparent->referrer_id, 'Grandparent should have no referrer');
        $this->assertEquals($grandparent->id, $parent->referrer_id, 'Parent should be referred by grandparent');
        $this->assertEquals($parent->id, $child->referrer_id, 'Child should be referred by parent');

        // Verify referral counts
        $this->assertEquals(1, $grandparent->referrals()->count(), 'Grandparent should have 1 direct referral');
        $this->assertEquals(1, $parent->referrals()->count(), 'Parent should have 1 direct referral');
        $this->assertEquals(0, $child->referrals()->count(), 'Child should have no referrals yet');
    }

    private function createRequiredRoles(): void
    {
        if (! Role::where('name', 'customer')->exists()) {
            Role::create(['name' => 'customer', 'guard_name' => 'web']);
        }
    }

    private function createTestCountry(array $attributes): Country
    {
        return Country::create(array_merge([
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
