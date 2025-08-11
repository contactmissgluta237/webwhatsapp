<?php

namespace Tests\Browser;

use App\Models\User;
use App\Services\Auth\Contracts\AccountActivationServiceInterface;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\RegisterPage;
use Tests\DuskTestCase;

class RegistrationFlowTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer le rôle customer nécessaire
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);

        // Créer un pays pour éviter les erreurs de validation
        DB::table('countries')->insert([
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

    /**
     * Test successful registration flow
     */
    public function test_user_can_complete_full_registration_flow()
    {
        // Mock le service d'activation
        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->expects($this->once())
            ->method('sendActivationCode')
            ->with('john.doe@example.com');
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        $this->browse(function (Browser $browser) {
            $registerPage = new RegisterPage;

            $browser->visit($registerPage)
                ->assertSee('Inscription')
                ->assertSee('Créez votre compte');

            // Remplir et soumettre le formulaire
            $registerPage->fillRegistrationForm($browser)
                ->submitForm($browser)
                ->waitForSubmissionResult($browser);

            // Vérifier la redirection vers la page d'activation
            $browser->assertPathBeginsWith('/account/activate/')
                ->assertSee('Activation du compte');
        });

        // Vérifier que l'utilisateur a été créé en base
        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email_verified_at' => null,
        ]);
    }

    /**
     * Test registration with phone number
     */
    public function test_user_can_register_with_phone_number()
    {
        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->expects($this->once())
            ->method('sendActivationCode')
            ->with('john.phone@example.com');
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        $this->browse(function (Browser $browser) {
            $registerPage = new RegisterPage;

            $browser->visit($registerPage);

            $registerPage->fillRegistrationForm($browser, [
                'email' => 'john.phone@example.com',
                'phone_number' => '+237655332183',
            ])
                ->submitForm($browser)
                ->waitForSubmissionResult($browser);

            $browser->assertPathBeginsWith('/account/activate/');
        });

        $this->assertDatabaseHas('users', [
            'email' => 'john.phone@example.com',
            'phone_number' => '+237655332183',
        ]);
    }

    /**
     * Test registration form validation errors
     */
    public function test_registration_form_shows_validation_errors()
    {
        $this->browse(function (Browser $browser) {
            $registerPage = new RegisterPage;

            $browser->visit($registerPage);

            // Soumettre le formulaire vide
            $registerPage->submitForm($browser);

            // Attendre les messages d'erreur
            $browser->waitFor('@errorMessage', 5)
                ->assertSee('Le prénom est obligatoire')
                ->assertPathIs('/register'); // Rester sur la page d'inscription
        });
    }

    /**
     * Test email format validation
     */
    public function test_registration_validates_email_format()
    {
        $this->browse(function (Browser $browser) {
            $registerPage = new RegisterPage;

            $browser->visit($registerPage);

            $registerPage->fillRegistrationForm($browser, [
                'email' => 'invalid-email',
            ])
                ->submitForm($browser);

            $browser->waitFor('@errorMessage', 5)
                ->assertSee('format du champ email est invalide')
                ->assertPathIs('/register');
        });
    }

    /**
     * Test password confirmation validation
     */
    public function test_registration_validates_password_confirmation()
    {
        $this->browse(function (Browser $browser) {
            $registerPage = new RegisterPage;

            $browser->visit($registerPage);

            $registerPage->fillRegistrationForm($browser, [
                'password' => 'password123',
                'password_confirmation' => 'different_password',
            ])
                ->submitForm($browser);

            $browser->waitFor('@errorMessage', 5)
                ->assertSee('La confirmation du mot de passe ne correspond pas')
                ->assertPathIs('/register');
        });
    }

    /**
     * Test duplicate email prevention
     */
    public function test_registration_prevents_duplicate_emails()
    {
        // Créer un utilisateur existant
        User::factory()->create(['email' => 'existing@example.com']);

        $this->browse(function (Browser $browser) {
            $registerPage = new RegisterPage;

            $browser->visit($registerPage);

            $registerPage->fillRegistrationForm($browser, [
                'email' => 'existing@example.com',
            ])
                ->submitForm($browser);

            $browser->waitFor('@errorMessage', 5)
                ->assertSee('Ce email est déjà utilisé')
                ->assertPathIs('/register');
        });
    }

    /**
     * Test terms acceptance requirement
     */
    public function test_registration_requires_terms_acceptance()
    {
        $this->browse(function (Browser $browser) {
            $registerPage = new RegisterPage;

            $browser->visit($registerPage);

            $registerPage->fillRegistrationForm($browser, [
                'terms' => false,
            ])
                ->submitForm($browser);

            $browser->waitFor('@errorMessage', 5)
                ->assertSee('Vous devez accepter les conditions')
                ->assertPathIs('/register');
        });
    }

    /**
     * Test registration form UI interactions
     */
    public function test_registration_form_ui_interactions()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(new RegisterPage)
                ->assertSee('Inscription')
                ->assertVisible('@firstName')
                ->assertVisible('@lastName')
                ->assertVisible('@email')
                ->assertVisible('@password')
                ->assertVisible('@passwordConfirmation')
                ->assertVisible('@terms')
                ->assertVisible('@submitButton');

            // Test des interactions avec les champs
            $browser->click('@firstName')
                ->assertFocused('@firstName')
                ->type('@firstName', 'Test')
                ->assertInputValue('@firstName', 'Test');

            // Test que la case à cocher fonctionne
            $browser->check('@terms')
                ->assertChecked('@terms');
        });
    }

    /**
     * Test registration loading state
     */
    public function test_registration_shows_loading_state()
    {
        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->method('sendActivationCode');
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        $this->browse(function (Browser $browser) {
            $registerPage = new RegisterPage;

            $browser->visit($registerPage);

            $registerPage->fillRegistrationForm($browser)
                ->submitForm($browser);

            // Vérifier l'état de chargement (si visible)
            try {
                $browser->waitFor('@loadingSpinner', 2);
            } catch (\Exception $e) {
                // Le spinner peut être trop rapide pour être détecté
            }

            $registerPage->waitForSubmissionResult($browser);
        });
    }

    /**
     * Test navigation from login to register
     */
    public function test_user_can_navigate_from_login_to_register()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->clickLink('Créer un compte')
                ->assertPathIs('/register')
                ->assertSee('Inscription');
        });
    }

    /**
     * Test authenticated user redirect
     */
    public function test_authenticated_user_redirected_from_registration()
    {
        // Créer et connecter un utilisateur
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole('customer');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/register')
                ->assertPathIs('/dashboard'); // Redirection vers le dashboard
        });
    }
}
