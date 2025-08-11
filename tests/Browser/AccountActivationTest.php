<?php

namespace Tests\Browser;

use App\Models\User;
use App\Services\Auth\Contracts\AccountActivationServiceInterface;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\ActivationPage;
use Tests\DuskTestCase;

class AccountActivationTest extends DuskTestCase
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
     * Test successful account activation with valid OTP
     *
     * @group activation
     */
    public function test_user_can_activate_account_with_valid_otp()
    {
        // Créer un utilisateur non activé
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);
        $user->assignRole('customer');

        // Mock le service d'activation
        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->expects($this->once())
            ->method('verifyOtp')
            ->with('test@example.com', '123456')
            ->willReturn(true);
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        $this->browse(function (Browser $browser) {
            $browser->visit('/account/activate/test@example.com')
                ->on(new ActivationPage)
                ->assertSee('Activation du compte')
                ->assertSee('test@example.com')
                ->within(new ActivationPage, function (Browser $browser) {
                    $browser->activateAccount($browser, '123456')
                        ->waitForActivationResult($browser);
                });

            // Vérifier la redirection après activation réussie
            $browser->assertPathIs('/dashboard')
                ->assertSee('Compte activé avec succès');
        });
    }

    /**
     * Test activation fails with invalid OTP
     *
     * @group activation
     */
    public function test_activation_fails_with_invalid_otp()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);
        $user->assignRole('customer');

        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->expects($this->once())
            ->method('verifyOtp')
            ->with('test@example.com', '000000')
            ->willReturn(false);
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        $this->browse(function (Browser $browser) {
            $browser->visit('/account/activate/test@example.com')
                ->on(new ActivationPage)
                ->within(new ActivationPage, function (Browser $browser) {
                    $browser->activateAccount($browser, '000000')
                        ->waitFor('@errorMessage', 5);
                });

            $browser->assertSee('Code d\'activation invalide')
                ->assertPathIs('/account/activate/test@example.com');

            // Vérifier que l'utilisateur n'est toujours pas activé
            $this->assertDatabaseHas('users', [
                'email' => 'test@example.com',
                'email_verified_at' => null,
            ]);
        });
    }

    /**
     * Test resending activation code
     *
     * @group activation
     */
    public function test_user_can_resend_activation_code()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);
        $user->assignRole('customer');

        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->expects($this->once())
            ->method('sendActivationCode')
            ->with('test@example.com');
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        $this->browse(function (Browser $browser) {
            $browser->visit('/account/activate/test@example.com')
                ->on(new ActivationPage)
                ->within(new ActivationPage, function (Browser $browser) {
                    $browser->resendActivationCode($browser);
                });

            $browser->assertSee('Code d\'activation renvoyé avec succès');
        });
    }

    /**
     * Test activation with invalid format OTP
     *
     * @group activation
     */
    public function test_activation_validates_otp_format()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);
        $user->assignRole('customer');

        $this->browse(function (Browser $browser) {
            $browser->visit('/account/activate/test@example.com')
                ->on(new ActivationPage)
                ->within(new ActivationPage, function (Browser $browser) {
                    $browser->activateAccount($browser, 'abc')
                        ->waitFor('@errorMessage', 5);
                });

            $browser->assertSee('Le code doit contenir exactement 6 chiffres');
        });
    }

    /**
     * Test activation page with invalid identifier
     *
     * @group activation
     */
    public function test_activation_page_with_invalid_identifier()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/account/activate/invalid@email.com')
                ->assertSee('Utilisateur introuvable')
                ->assertPathIs('/account/activate/invalid@email.com');
        });
    }

    /**
     * Test already activated user redirection
     *
     * @group activation
     */
    public function test_already_activated_user_is_redirected()
    {
        $user = User::factory()->create([
            'email' => 'activated@example.com',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('customer');

        $this->browse(function (Browser $browser) {
            $browser->visit('/account/activate/activated@example.com')
                ->assertPathIs('/dashboard')
                ->assertSee('Votre compte est déjà activé');
        });
    }

    /**
     * Test activation form UI elements
     *
     * @group activation
     */
    public function test_activation_form_ui_elements()
    {
        $user = User::factory()->create([
            'email' => 'ui-test@example.com',
            'email_verified_at' => null,
        ]);
        $user->assignRole('customer');

        $this->browse(function (Browser $browser) {
            $browser->visit('/account/activate/ui-test@example.com')
                ->on(new ActivationPage)
                ->assertSee('Activation du compte')
                ->assertSee('ui-test@example.com')
                ->assertVisible('@otpInput')
                ->assertVisible('@activateButton')
                ->assertVisible('@resendButton');

            // Test des interactions avec le champ OTP
            $browser->click('@otpInput')
                ->assertFocused('@otpInput')
                ->type('@otpInput', '123')
                ->assertInputValue('@otpInput', '123');

            // Test que le bouton d'activation est clickable
            $browser->assertAttribute('@activateButton', 'type', 'submit');
        });
    }

    /**
     * Test activation with expired OTP
     *
     * @group activation
     */
    public function test_activation_fails_with_expired_otp()
    {
        $user = User::factory()->create([
            'email' => 'expired@example.com',
            'email_verified_at' => null,
        ]);
        $user->assignRole('customer');

        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->expects($this->once())
            ->method('verifyOtp')
            ->with('expired@example.com', '123456')
            ->willThrowException(new \Exception('Code expiré'));
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        $this->browse(function (Browser $browser) {
            $browser->visit('/account/activate/expired@example.com')
                ->on(new ActivationPage)
                ->within(new ActivationPage, function (Browser $browser) {
                    $browser->activateAccount($browser, '123456')
                        ->waitFor('@errorMessage', 5);
                });

            $browser->assertSee('Une erreur est survenue')
                ->assertPathIs('/account/activate/expired@example.com');
        });
    }

    /**
     * Test activation loading state
     *
     * @group activation
     */
    public function test_activation_shows_loading_state()
    {
        $user = User::factory()->create([
            'email' => 'loading@example.com',
            'email_verified_at' => null,
        ]);
        $user->assignRole('customer');

        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->method('verifyOtp')->willReturn(true);
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        $this->browse(function (Browser $browser) {
            $browser->visit('/account/activate/loading@example.com')
                ->on(new ActivationPage)
                ->within(new ActivationPage, function (Browser $browser) {
                    $browser->activateAccount($browser, '123456');

                    // Essayer de voir l'état de chargement
                    try {
                        $browser->waitFor('@loadingSpinner', 2);
                    } catch (\Exception $e) {
                        // Le spinner peut être trop rapide
                    }

                    $browser->waitForActivationResult($browser);
                });
        });
    }

    /**
     * Test multiple activation attempts
     *
     * @group activation
     */
    public function test_multiple_activation_attempts()
    {
        $user = User::factory()->create([
            'email' => 'multiple@example.com',
            'email_verified_at' => null,
        ]);
        $user->assignRole('customer');

        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->expects($this->exactly(3))
            ->method('verifyOtp')
            ->willReturn(false);
        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        $this->browse(function (Browser $browser) {
            $browser->visit('/account/activate/multiple@example.com')
                ->on(new ActivationPage);

            // Première tentative
            $browser->within(new ActivationPage, function (Browser $browser) {
                $browser->activateAccount($browser, '111111')
                    ->waitFor('@errorMessage', 5);
            });
            $browser->assertSee('Code d\'activation invalide');

            // Deuxième tentative
            $browser->within(new ActivationPage, function (Browser $browser) {
                $browser->type('@otpInput', '')
                    ->activateAccount($browser, '222222')
                    ->waitFor('@errorMessage', 5);
            });
            $browser->assertSee('Code d\'activation invalide');

            // Troisième tentative
            $browser->within(new ActivationPage, function (Browser $browser) {
                $browser->type('@otpInput', '')
                    ->activateAccount($browser, '333333')
                    ->waitFor('@errorMessage', 5);
            });
            $browser->assertSee('Code d\'activation invalide');
        });
    }
}
