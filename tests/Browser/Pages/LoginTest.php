<?php

namespace Tests\Browser\Pages;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    /**
     * Test connexion avec email
     */
    public function test_user_can_login_with_email()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitFor('input[name="email"]', 5) // Attendre 5 secondes
                ->type('email', env('ADMIN_EMAIL'))
                ->type('password', env('ADMIN_PASSWORD'))
                ->press('button[type="submit"]') // Ou le texte du bouton
                ->waitForLocation('/dashboard', 10) // Attendre redirection
                ->assertSee('Tableau de bord'); // Texte confirmant la connexion
        });
    }

    /**
     * Test connexion avec téléphone (si votre app le permet)
     */
    public function test_user_can_login_with_phone()
    {
        // Créer un utilisateur
        $user = User::create([
            'name' => 'Test User Phone',
            'email' => 'phone@test.com',
            'phone' => env('TEST_USER_PHONE'),
            'password' => Hash::make(env('TEST_USER_PASSWORD')),
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitFor('input[name="phone"]', 5)
                ->type('phone', env('TEST_USER_PHONE'))
                ->type('password', env('TEST_USER_PASSWORD'))
                ->press('button[type="submit"]')
                ->waitForLocation('/dashboard', 10)
                ->assertSee('Tableau de bord');
        });
    }

    /**
     * Test échec de connexion
     */
    public function test_login_fails_with_invalid_credentials()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitFor('input[name="email"]', 5)
                ->type('email', 'wrong@email.com')
                ->type('password', 'wrongpassword')
                ->press('button[type="submit"]')
                ->waitFor('.alert-danger', 5) // Attendre message d'erreur
                ->assertSee('Ces identifiants ne correspondent pas'); // Message d'erreur
        });
    }

    /**
     * Test redirection après connexion réussie
     */
    public function test_successful_login_redirects_to_dashboard()
    {
        $user = User::create([
            'name' => 'Test Redirect',
            'email' => env('TEST_USER_EMAIL'),
            'password' => Hash::make(env('TEST_USER_PASSWORD')),
            'email_verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitFor('input[name="email"]', 5)
                ->type('email', env('TEST_USER_EMAIL'))
                ->type('password', env('TEST_USER_PASSWORD'))
                ->press('button[type="submit"]')
                ->waitForLocation('/dashboard', 10)
                ->assertPathIs('/dashboard')
                ->assertDontSee('Se connecter'); // Plus de lien login
        });
    }
}
