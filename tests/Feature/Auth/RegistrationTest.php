<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\RegisterForm;
use App\Models\User;
use App\Services\Auth\Contracts\AccountActivationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer manuellement le rôle customer pour éviter les problèmes de seeder
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);

        // Créer un pays avec l'ID 1 pour éviter les erreurs de validation
        \Illuminate\Support\Facades\DB::table('countries')->insert([
            'id' => 1,
            'name' => 'Cameroon',
            'code' => 'CM',
            'phone_code' => '+237',
            'flag_emoji' => '🇨🇲',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function users_can_view_registration_form()
    {
        $response = $this->get(route('register'));

        $response->assertSuccessful();
        $response->assertSeeLivewire(RegisterForm::class);
    }

    /** @test */
    public function users_can_register_with_valid_data()
    {
        Mail::fake();

        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->expects($this->once())->method('sendActivationCode');
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        Livewire::test(RegisterForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('terms', true)
            ->call('register')
            ->assertRedirect(route('account.activate', ['identifier' => 'john.doe@example.com']));

        $this->assertDatabaseHas('users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
        ]);

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
    }

    /** @test */
    public function registration_fails_with_invalid_email()
    {
        Livewire::test(RegisterForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('email', 'invalid-email')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('terms', true)
            ->call('register')
            ->assertHasErrors(['email']);

        $this->assertDatabaseMissing('users', ['email' => 'invalid-email']);
    }

    /** @test */
    public function registration_fails_with_duplicate_email()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(RegisterForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('email', 'existing@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('terms', true)
            ->call('register')
            ->assertHasErrors(['email']);
    }

    /** @test */
    public function registration_fails_without_accepting_terms()
    {
        Livewire::test(RegisterForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('terms', false)
            ->call('register')
            ->assertHasErrors(['terms']);

        $this->assertDatabaseMissing('users', ['email' => 'john.doe@example.com']);
    }

    /** @test */
    public function registration_fails_with_password_mismatch()
    {
        Livewire::test(RegisterForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'different_password')
            ->set('terms', true)
            ->call('register')
            ->assertHasErrors(['password']);

        $this->assertDatabaseMissing('users', ['email' => 'john.doe@example.com']);
    }

    /** @test */
    public function registration_fails_with_missing_required_fields()
    {
        Livewire::test(RegisterForm::class)
            ->call('register')
            ->assertHasErrors([
                'first_name',
                'last_name',
                'email',
                'password',
                'terms',
            ]);
    }

    /** @test */
    public function users_can_register_with_phone_number()
    {
        Mail::fake();

        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->expects($this->once())->method('sendActivationCode');
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        $phoneData = [
            'name' => 'phone_number',
            'value' => '+237655332183',
            'country_id' => 1,
            'phone_number' => '655332183',
        ];

        Livewire::test(RegisterForm::class)
            ->call('phoneUpdated', $phoneData)
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('terms', true)
            ->call('register')
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'phone_number' => '+237655332183',
        ]);
    }

    /** @test */
    public function registration_creates_user_with_customer_role()
    {
        Mail::fake();

        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->expects($this->once())->method('sendActivationCode');
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        Livewire::test(RegisterForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('terms', true)
            ->call('register');

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('customer'));
    }

    /** @test */
    public function registration_redirects_authenticated_users()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('register'));

        $response->assertRedirect('/');
    }

    /** @test */
    public function registration_handles_activation_service_failure()
    {
        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->method('sendActivationCode')
            ->willThrowException(new \Exception('Activation service failed'));
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        Livewire::test(RegisterForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('email', 'john.doe@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('terms', true)
            ->call('register')
            ->assertSet('error', 'Une erreur est survenue lors de la création du compte. Veuillez réessayer.');

        $this->assertDatabaseMissing('users', ['email' => 'john.doe@example.com']);
    }
}
