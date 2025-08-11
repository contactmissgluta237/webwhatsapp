<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginFlowTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

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

        // Define a dummy dashboard route for testing purposes
        \Illuminate\Support\Facades\Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');
    }

    /** @test */
    public function user_can_login_with_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertSee('Connexion')
                ->type('@email', 'test@example.com')
                ->type('@password', 'password123')
                ->click('@login-button')
                ->waitForRoute('dashboard')
                ->assertRouteIs('dashboard');
        });
    }

    /** @test */
    public function user_can_login_with_phone()
    {
        $user = User::factory()->create([
            'phone_number' => '+237655332183',
            'password' => bcrypt('password123'),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertSee('Connexion')
                ->click('@phone-tab')
                ->waitFor('@phone-input')
                ->type('@phone-input', '655332183')
                ->type('@password', 'password123')
                ->click('@login-button')
                ->waitForRoute('dashboard')
                ->assertRouteIs('dashboard');
        });
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('@email', 'test@example.com')
                ->type('@password', 'wrongpassword')
                ->click('@login-button')
                ->waitForText('Identifiants incorrects')
                ->assertSee('Identifiants incorrects. Veuillez réessayer.');
        });
    }

    /** @test */
    public function user_can_navigate_to_forgot_password()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->click('@forgot-password-link')
                ->waitForRoute('password.request')
                ->assertRouteIs('password.request')
                ->assertSee('Mot de passe oublié');
        });
    }

    /** @test */
    public function user_can_switch_between_email_and_phone_login()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertSee('@email')
                ->assertDontSee('@phone-input')
                ->click('@phone-tab')
                ->waitFor('@phone-input')
                ->assertDontSee('@email')
                ->click('@email-tab')
                ->waitFor('@email')
                ->assertDontSee('@phone-input');
        });
    }

    /** @test */
    public function login_form_validates_required_fields()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->click('@login-button')
                ->waitForText('L\'email est requis')
                ->assertSee('L\'email est requis')
                ->assertSee('Le mot de passe est requis');
        });
    }

    /** @test */
    public function authenticated_user_is_redirected_from_login()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/login')
                ->waitForRoute('dashboard')
                ->assertRouteIs('dashboard');
        });
    }

    /** @test */
    public function user_can_logout()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->click('@logout-button')
                ->waitForRoute('login')
                ->assertRouteIs('login');
        });
    }
}
