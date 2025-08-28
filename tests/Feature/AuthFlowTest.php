<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Auth\LoginForm;
use App\Livewire\Auth\RegisterForm;
use App\Models\User;
use App\Services\Auth\Contracts\AccountActivationServiceInterface;
use App\Services\SMS\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSeeLivewire(LoginForm::class);
    }

    #[Test]
    public function test_registration_page_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSeeLivewire(RegisterForm::class);
    }

    #[Test]
    public function test_forgot_password_page_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertViewIs('auth.forgot-password');
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function user_with_phone_number_can_be_created(): void
    {
        $user = User::factory()->create([
            'phone_number' => '+237655332183',
            'password' => bcrypt('password'),
        ]);

        $user->assignRole('customer');

        // Vérifier que l'utilisateur avec numéro de téléphone est bien créé
        $this->assertNotNull($user);
        $this->assertEquals('+237655332183', $user->phone_number);
        $this->assertTrue($user->hasRole('customer'));
    }

    #[Test]
    public function authenticated_user_is_redirected_from_auth_pages(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $this->actingAs($user);

        // Vérifier la redirection depuis les pages d'auth
        $this->get('/login')->assertRedirect('/');
        $this->get('/register')->assertRedirect('/');
        $this->get('/forgot-password')->assertRedirect('/');
    }

    #[Test]
    public function user_can_logout(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    #[Test]
    public function login_rate_limiting_works(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Faire plusieurs tentatives échouées
        for ($i = 0; $i < 5; $i++) {
            Livewire::test(LoginForm::class)
                ->set('email', 'test@example.com')
                ->set('password', 'wrong-password')
                ->set('loginMethod', 'email')
                ->call('login');
        }

        // La prochaine tentative devrait être limitée
        Livewire::test(LoginForm::class)
            ->set('email', 'test@example.com')
            ->set('password', 'password') // Bon mot de passe cette fois
            ->set('loginMethod', 'email')
            ->call('login')
            ->assertSet('error', 'Une erreur est survenue. Veuillez réessayer.');
    }

    #[Test]
    public function registration_creates_user_correctly(): void
    {
        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->expects($this->once())->method('sendActivationCode');
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        Livewire::test(RegisterForm::class)
            ->set('first_name', 'Test')
            ->set('last_name', 'User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('terms', true)
            ->call('register');

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Test', $user->first_name);
        $this->assertEquals('User', $user->last_name);
    }

    #[Test]
    public function user_registration_with_valid_data_completes_successfully(): void
    {
        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->expects($this->once())->method('sendActivationCode');
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        Livewire::test(RegisterForm::class)
            ->set('first_name', 'Test')
            ->set('last_name', 'User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('terms', true)
            ->call('register')
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);
    }
}
