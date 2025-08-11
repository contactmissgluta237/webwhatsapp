<?php

namespace Tests\Unit\Livewire\Auth;

use App\Enums\LoginChannel;
use App\Livewire\Auth\LoginForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
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

    /** @test */
    public function it_renders_successfully()
    {
        Livewire::test(LoginForm::class)->assertSuccessful();
    }

    /** @test */
    public function it_validates_required_fields()
    {
        Livewire::test(LoginForm::class)
            ->call('login')
            ->assertHasErrors([
                'email' => 'required',
                'password' => 'required',
            ]);
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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
}
