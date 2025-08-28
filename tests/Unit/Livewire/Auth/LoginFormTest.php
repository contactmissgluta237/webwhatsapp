<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire\Auth;

use App\Enums\LoginChannel;
use App\Livewire\Auth\LoginForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Define a dummy dashboard route for testing purposes
        \Illuminate\Support\Facades\Route::get('/dashboard', function () {
            return 'Dashboard';
        })->name('dashboard');
    }

    #[Test]
    public function it_renders_successfully()
    {
        Livewire::test(LoginForm::class)->assertSuccessful();
    }

    #[Test]
    public function it_validates_required_fields()
    {
        Livewire::test(LoginForm::class)
            ->call('login')
            ->assertHasErrors([
                'email' => 'required',
                'password' => 'required',
            ]);
    }

    #[Test]
    public function it_logs_in_user_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        Livewire::test(LoginForm::class)
            ->set('email', 'test@example.com')
            ->set('loginMethod', 'email')
            ->set('password', 'password123')
            ->call('login')
            ->assertSuccessful();

        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }

    #[Test]
    public function it_fails_login_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        Livewire::test(LoginForm::class)
            ->set('email', 'test@example.com')
            ->set('loginMethod', 'email')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertSet('error', 'Identifiants incorrects. Veuillez réessayer.');

        $this->assertFalse(Auth::check());
    }

    #[Test]
    public function it_sets_loading_state_during_login()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        Livewire::test(LoginForm::class)
            ->set('email', 'test@example.com')
            ->set('loginMethod', 'email')
            ->set('password', 'password123')
            ->call('login')
            ->assertSet('loading', false);
    }

    #[Test]
    public function it_logs_in_user_with_valid_phone_credentials()
    {
        $this->seed('CountrySeeder');

        $user = User::factory()->create([
            'phone_number' => '+237655332183',
            'password' => bcrypt('password123'),
        ]);

        Livewire::test(LoginForm::class)
            ->call('phoneUpdated', [
                'name' => 'phone_number',
                'value' => '+237655332183',
                'country_id' => 1,
                'phone_number' => '655332183',
            ])
            ->set('loginMethod', LoginChannel::PHONE())
            ->set('password', 'password123')
            ->call('login')
            ->assertSuccessful();

        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }

    #[Test]
    public function it_validates_email_format(): void
    {
        Livewire::test(LoginForm::class)
            ->set('email', 'invalid-email-format')
            ->set('password', 'password123')
            ->set('loginMethod', 'email')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    #[Test]
    public function it_handles_login_method_switching(): void
    {
        $component = Livewire::test(LoginForm::class);

        // Commencer avec email
        $component->set('loginMethod', 'email')
            ->assertSet('loginMethod', 'email');

        // Changer pour téléphone
        $component->set('loginMethod', LoginChannel::PHONE())
            ->assertSet('loginMethod', LoginChannel::PHONE());
    }

    #[Test]
    public function it_clears_error_messages_on_new_attempt(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $component = Livewire::test(LoginForm::class)
            ->set('email', 'test@example.com')
            ->set('loginMethod', 'email')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertSet('error', 'Identifiants incorrects. Veuillez réessayer.');

        // Nouvelle tentative doit nettoyer l'erreur
        $component->set('password', 'password123')
            ->call('login')
            ->assertSet('error', null);
    }

    #[Test]
    public function it_remembers_user_when_remember_is_checked(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        Livewire::test(LoginForm::class)
            ->set('email', 'test@example.com')
            ->set('loginMethod', 'email')
            ->set('password', 'password123')
            ->set('remember', true)
            ->call('login')
            ->assertSuccessful();

        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }

    #[Test]
    public function it_requires_phone_number_for_phone_login(): void
    {
        Livewire::test(LoginForm::class)
            ->set('loginMethod', LoginChannel::PHONE())
            ->set('password', 'password123')
            ->call('login')
            ->assertHasErrors(['phone_number']);
    }
}
