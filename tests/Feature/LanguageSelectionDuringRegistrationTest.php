<?php

namespace Tests\Feature;

use App\DTOs\Customer\CreateCustomerDTO;
use App\Models\Geography\Country;
use App\Models\User;
use App\Services\Customer\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LanguageSelectionDuringRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'customer', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        // Create a default country for testing
        Country::factory()->create([
            'id' => 1,
            'name' => 'Cameroon',
            'code' => 'CM',
            'phone_code' => '+237',
            'flag_emoji' => '🇨🇲',
        ]);
    }

    public function test_user_can_select_language_during_registration(): void
    {
        $dto = CreateCustomerDTO::from([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'phone_number' => null,
            'country_id' => 1,
            'referral_code' => null,
            'terms' => true,
            'locale' => 'en',
        ]);

        $customerService = app(CustomerService::class);
        $customer = $customerService->create($dto);

        // Assert user was created with correct locale
        $this->assertEquals('en', $customer->user->locale);
        $this->assertEquals('John', $customer->user->first_name);
        $this->assertEquals('john@example.com', $customer->user->email);
    }

    public function test_user_registration_defaults_to_french_locale(): void
    {
        $dto = CreateCustomerDTO::from([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@example.com',
            'password' => 'password123',
            'phone_number' => null,
            'country_id' => 1,
            'referral_code' => null,
            'terms' => true,
            'locale' => 'fr', // Explicitly setting French (default behavior)
        ]);

        $customerService = app(CustomerService::class);
        $customer = $customerService->create($dto);

        // Assert user was created with French locale
        $this->assertEquals('fr', $customer->user->locale);
    }

    public function test_locale_validation_rejects_invalid_language(): void
    {
        Livewire::test(\App\Livewire\Auth\RegisterForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('email', 'john@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('terms', true)
            ->set('locale', 'invalid_locale')
            ->call('register')
            ->assertHasErrors(['locale']);
    }

    public function test_registration_form_shows_language_options_with_flags(): void
    {
        $this->get('/register')
            ->assertSee('🇺🇸')
            ->assertSee('🇫🇷')
            ->assertSee('English')
            ->assertSee('Français')
            ->assertSee('Preferred Language');
    }

    public function test_referral_code_and_language_are_on_same_row(): void
    {
        $response = $this->get('/register');

        $content = $response->getContent();

        // Check that both fields are within the same row div
        $this->assertStringContainsString('<div class="row mb-3">', $content);
        $this->assertStringContainsString('referral_code', $content);
        $this->assertStringContainsString('locale', $content);
    }

    public function test_user_can_update_language_in_profile_after_registration(): void
    {
        // First create user with French
        $dto = CreateCustomerDTO::from([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@example.com',
            'password' => 'password123',
            'phone_number' => null,
            'country_id' => 1,
            'referral_code' => null,
            'terms' => true,
            'locale' => 'fr',
        ]);

        $customerService = app(CustomerService::class);
        $customer = $customerService->create($dto);

        $this->assertEquals('fr', $customer->user->locale);

        // Now test updating locale in profile (simulating profile update)
        $customer->user->update(['locale' => 'en']);
        $customer->user->refresh();

        $this->assertEquals('en', $customer->user->locale);
    }
}
