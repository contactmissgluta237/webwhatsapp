<?php

namespace Tests\Feature;

use App\Livewire\Auth\LoginForm;
use App\Livewire\Auth\RegisterForm;
use App\Models\User;
use App\Services\Auth\Contracts\AccountActivationServiceInterface;
use App\Services\SMS\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer le rôle customer pour les tests d'inscription
        Role::create(['name' => 'customer']);

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

        // Mock du service SMS pour éviter les erreurs Twilio
        $mockSmsService = $this->createMock(SmsServiceInterface::class);
        $mockSmsService->method('sendSms')->willReturn(true);
        $this->app->instance(SmsServiceInterface::class, $mockSmsService);
    }

    /**
     * Test that the login page can be rendered.
     */
    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Connexion');
    }

    /**
     * Test that the registration page can be rendered.
     */
    public function test_registration_page_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Inscription');
    }

    /**
     * Test that the forgot password page can be rendered.
     */
    public function test_forgot_password_page_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertSee('Mot de passe oublié');
    }

    /**
     * Test user registration.
     */
    public function test_user_can_register(): void
    {
        // Mock du service d'activation
        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->expects($this->once())->method('sendActivationCode');
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        Livewire::test(RegisterForm::class)
            ->set('first_name', 'Test')
            ->set('last_name', 'User')
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('terms', true)
            ->call('register')
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    /**
     * Test user registration with invalid data.
     */
    public function test_user_cannot_register_with_invalid_data(): void
    {
        Livewire::test(RegisterForm::class)
            ->set('first_name', '')
            ->set('last_name', '')
            ->set('email', 'invalid-email')
            ->set('password', 'short')
            ->set('password_confirmation', 'not-matching')
            ->set('terms', false)
            ->call('register')
            ->assertHasErrors(['first_name', 'last_name', 'email', 'password', 'terms']);

        $this->assertDatabaseMissing('users', [
            'email' => 'invalid-email',
        ]);
    }

    /**
     * Test user login.
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Assigner un rôle à l'utilisateur pour que la redirection fonctionne
        $user->assignRole('customer');

        Livewire::test(LoginForm::class)
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('loginMethod', 'email')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test user login with incorrect credentials.
     */
    public function test_user_cannot_login_with_incorrect_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        Livewire::test(LoginForm::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrong-password')
            ->set('loginMethod', 'email')
            ->call('login')
            ->assertSet('error', 'Identifiants incorrects. Veuillez réessayer.');

        $this->assertGuest();
    }
}
