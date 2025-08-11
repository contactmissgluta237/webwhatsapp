<?php

namespace Tests\Browser;

use App\Models\User;
use App\Services\Auth\Contracts\OtpServiceInterface;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PasswordResetFlowTest extends DuskTestCase
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

        // Define routes for testing
        \Illuminate\Support\Facades\Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');
    }

    /** @test */
    public function user_can_complete_password_reset_flow_with_email()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Mock OTP service
        $otpService = $this->createMock(OtpServiceInterface::class);
        $otpService->method('verifyOtp')->willReturn(true);
        $otpService->method('generateResetToken')->willReturn('valid-token');
        $this->app->instance(OtpServiceInterface::class, $otpService);

        $this->browse(function (Browser $browser) {
            // Step 1: Request password reset
            $browser->visit('/password/reset')
                ->assertSee('Mot de passe oublié')
                ->type('@email', 'test@example.com')
                ->click('@send-reset-button')
                ->waitForRoute('password.verify.otp')
                ->assertRouteIs('password.verify.otp');

            // Step 2: Verify OTP
            $browser->type('@otp-code', '123456')
                ->click('@verify-otp-button')
                ->waitForRoute('password.reset')
                ->assertRouteIs('password.reset');

            // Step 3: Reset password
            $browser->type('@password', 'newpassword123')
                ->type('@password-confirmation', 'newpassword123')
                ->click('@reset-password-button')
                ->waitForRoute('login')
                ->assertRouteIs('login')
                ->assertSee('Votre mot de passe a été réinitialisé avec succès');
        });
    }

    /** @test */
    public function user_can_complete_password_reset_flow_with_phone()
    {
        $user = User::factory()->create(['phone_number' => '+237655332183']);

        // Mock OTP service
        $otpService = $this->createMock(OtpServiceInterface::class);
        $otpService->method('verifyOtp')->willReturn(true);
        $otpService->method('generateResetToken')->willReturn('valid-token');
        $this->app->instance(OtpServiceInterface::class, $otpService);

        $this->browse(function (Browser $browser) {
            // Step 1: Request password reset with phone
            $browser->visit('/password/reset')
                ->click('@phone-tab')
                ->waitFor('@phone-input')
                ->type('@phone-input', '655332183')
                ->click('@send-reset-button')
                ->waitForRoute('password.verify.otp')
                ->assertRouteIs('password.verify.otp');

            // Step 2: Verify OTP
            $browser->type('@otp-code', '123456')
                ->click('@verify-otp-button')
                ->waitForRoute('password.reset.phone')
                ->assertRouteIs('password.reset.phone');

            // Step 3: Reset password
            $browser->type('@password', 'newpassword123')
                ->type('@password-confirmation', 'newpassword123')
                ->click('@reset-password-button')
                ->waitForRoute('login')
                ->assertRouteIs('login')
                ->assertSee('Votre mot de passe a été réinitialisé avec succès');
        });
    }

    /** @test */
    public function user_can_switch_between_email_and_phone_reset_methods()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/password/reset')
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
    public function user_sees_error_for_nonexistent_email()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/password/reset')
                ->type('@email', 'nonexistent@example.com')
                ->click('@send-reset-button')
                ->waitForText('Aucun compte')
                ->assertSee('Aucun compte n\'est associé à cet identifiant');
        });
    }

    /** @test */
    public function user_sees_error_for_invalid_otp()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Mock OTP service to return false for verification
        $otpService = $this->createMock(OtpServiceInterface::class);
        $otpService->method('verifyOtp')->willReturn(false);
        $this->app->instance(OtpServiceInterface::class, $otpService);

        $this->browse(function (Browser $browser) {
            // Navigate to OTP verification page
            $browser->visit('/password/verify-otp?email=test@example.com&resetType=email')
                ->type('@otp-code', '999999')
                ->click('@verify-otp-button')
                ->waitForText('Code de vérification incorrect')
                ->assertSee('Code de vérification incorrect. Veuillez réessayer.');
        });
    }

    /** @test */
    public function user_can_resend_otp()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Mock OTP service
        $otpService = $this->createMock(OtpServiceInterface::class);
        $otpService->expects($this->once())->method('sendOtp');
        $this->app->instance(OtpServiceInterface::class, $otpService);

        $this->browse(function (Browser $browser) {
            $browser->visit('/password/verify-otp?email=test@example.com&resetType=email')
                ->click('@resend-otp-button')
                ->waitForText('Un nouveau code a été envoyé')
                ->assertSee('Un nouveau code a été envoyé');
        });
    }

    /** @test */
    public function user_can_go_back_to_forgot_password_from_otp()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/password/verify-otp?email=test@example.com&resetType=email')
                ->click('@back-to-forgot-password-button')
                ->waitForRoute('password.request')
                ->assertRouteIs('password.request');
        });
    }

    /** @test */
    public function user_can_go_back_to_login_from_forgot_password()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/password/reset')
                ->click('@back-to-login-button')
                ->waitForRoute('login')
                ->assertRouteIs('login');
        });
    }

    /** @test */
    public function reset_password_form_validates_password_confirmation()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $this->browse(function (Browser $browser) {
            $browser->visit('/password/reset/valid-token?email=test@example.com')
                ->type('@password', 'newpassword123')
                ->type('@password-confirmation', 'differentpassword')
                ->click('@reset-password-button')
                ->waitForText('La confirmation du mot de passe ne correspond pas')
                ->assertSee('La confirmation du mot de passe ne correspond pas');
        });
    }

    /** @test */
    public function reset_password_form_validates_minimum_password_length()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $this->browse(function (Browser $browser) {
            $browser->visit('/password/reset/valid-token?email=test@example.com')
                ->type('@password', '123')
                ->type('@password-confirmation', '123')
                ->click('@reset-password-button')
                ->waitForText('Le mot de passe doit contenir au moins')
                ->assertSee('Le mot de passe doit contenir au moins');
        });
    }

    /** @test */
    public function authenticated_user_is_redirected_from_password_reset()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/password/reset')
                ->waitForRoute('dashboard')
                ->assertRouteIs('dashboard');
        });
    }
}
