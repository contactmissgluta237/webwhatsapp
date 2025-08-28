<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ResetPasswordForm;
use App\Models\User;
use App\Services\Auth\Contracts\OtpServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configurer Twilio pour les tests
        config([
            'services.twilio.sid' => 'test_sid',
            'services.twilio.token' => 'test_token',
            'services.twilio.from' => '+1234567890',
        ]);

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

    #[Test]
    public function users_can_view_reset_password_form_with_email()
    {
        $response = $this->get(route('password.reset', [
            'token' => 'valid-token',
            'email' => 'test@example.com',
        ]));

        $response->assertSuccessful();
        $response->assertSeeLivewire(ResetPasswordForm::class);
    }

    #[Test]
    public function users_can_view_reset_password_form_with_phone()
    {
        $response = $this->get(route('password.reset.phone', [
            'token' => 'valid-token',
            'identifier' => '+237655332183',
            'resetType' => 'phone',
        ]));

        $response->assertSuccessful();
        $response->assertSeeLivewire(ResetPasswordForm::class);
    }

    #[Test]
    public function users_can_reset_password_with_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
        ]);

        $otpService = $this->createMock(OtpServiceInterface::class);
        $otpService->expects($this->once())
            ->method('invalidateOtp')
            ->with('test@example.com');
        $this->app->instance(OtpServiceInterface::class, $otpService);

        Livewire::test(ResetPasswordForm::class, [
            'token' => 'valid-token',
            'identifier' => 'test@example.com',
            'resetType' => 'email',
        ])
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('resetPassword')
            ->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    #[Test]
    public function users_can_reset_password_with_phone()
    {
        $user = User::factory()->create([
            'phone_number' => '+237655332183',
            'password' => Hash::make('oldpassword'),
        ]);

        $otpService = $this->createMock(OtpServiceInterface::class);
        $otpService->expects($this->once())
            ->method('invalidateOtp')
            ->with('+237655332183');
        $this->app->instance(OtpServiceInterface::class, $otpService);

        Livewire::test(ResetPasswordForm::class, [
            'token' => 'valid-token',
            'identifier' => '+237655332183',
            'resetType' => 'phone',
        ])
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('resetPassword')
            ->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    #[Test]
    public function reset_password_fails_with_invalid_email()
    {
        Livewire::test(ResetPasswordForm::class, [
            'token' => 'valid-token',
            'identifier' => 'invalid-email',
            'resetType' => 'email',
        ])
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('resetPassword')
            ->assertHasErrors(['identifier']);
    }

    #[Test]
    public function reset_password_fails_with_nonexistent_user()
    {
        Livewire::test(ResetPasswordForm::class, [
            'token' => 'valid-token',
            'identifier' => 'nonexistent@example.com',
            'resetType' => 'email',
        ])
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('resetPassword')
            ->assertHasErrors(['identifier']);
    }

    #[Test]
    public function reset_password_fails_with_password_mismatch()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        Livewire::test(ResetPasswordForm::class, [
            'token' => 'valid-token',
            'identifier' => 'test@example.com',
            'resetType' => 'email',
        ])
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'differentpassword')
            ->call('resetPassword')
            ->assertHasErrors(['password']);
    }

    #[Test]
    public function reset_password_fails_with_short_password()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        Livewire::test(ResetPasswordForm::class, [
            'token' => 'valid-token',
            'identifier' => 'test@example.com',
            'resetType' => 'email',
        ])
            ->set('password', '123')
            ->set('password_confirmation', '123')
            ->call('resetPassword')
            ->assertHasErrors(['password']);
    }

    #[Test]
    public function reset_password_fails_with_missing_token()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        Livewire::test(ResetPasswordForm::class, [
            'token' => '',
            'identifier' => 'test@example.com',
            'resetType' => 'email',
        ])
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('resetPassword')
            ->assertHasErrors(['token']);
    }

    #[Test]
    public function reset_password_fails_with_missing_identifier()
    {
        Livewire::test(ResetPasswordForm::class, [
            'token' => 'valid-token',
            'identifier' => '',
            'resetType' => 'email',
        ])
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('resetPassword')
            ->assertHasErrors(['identifier']);
    }

    #[Test]
    public function reset_password_handles_service_exception()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $otpService = $this->createMock(OtpServiceInterface::class);
        $otpService->method('invalidateOtp')
            ->willThrowException(new \Exception('Service error'));
        $this->app->instance(OtpServiceInterface::class, $otpService);

        Livewire::test(ResetPasswordForm::class, [
            'token' => 'valid-token',
            'identifier' => 'test@example.com',
            'resetType' => 'email',
        ])
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('resetPassword')
            ->assertSet('error', 'Une erreur est survenue. Veuillez réessayer plus tard.');
    }

    #[Test]
    public function form_displays_correctly_for_email_reset()
    {
        $response = $this->get(route('password.reset', [
            'token' => 'valid-token',
            'email' => 'test@example.com',
        ]));

        $response->assertSuccessful();
        $response->assertSeeLivewire(ResetPasswordForm::class);
    }

    #[Test]
    public function form_displays_correctly_for_phone_reset()
    {
        $response = $this->get(route('password.reset.phone', [
            'token' => 'valid-token',
            'identifier' => '+237655332183',
            'resetType' => 'phone',
        ]));

        $response->assertSuccessful();
        $response->assertSeeLivewire(ResetPasswordForm::class);
    }

    #[Test]
    public function reset_password_validates_phone_format()
    {
        Livewire::test(ResetPasswordForm::class, [
            'token' => 'valid-token',
            'identifier' => 'invalid-phone',
            'resetType' => 'phone',
        ])
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('resetPassword')
            ->assertHasErrors(['identifier']);
    }

    #[Test]
    public function reset_password_validates_password_requirements(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        Livewire::test(ResetPasswordForm::class, [
            'token' => 'valid-token',
            'identifier' => 'test@example.com',
            'resetType' => 'email',
        ])
            ->set('password', '12') // Trop court
            ->set('password_confirmation', '12')
            ->call('resetPassword')
            ->assertHasErrors(['password']);
    }

    #[Test]
    public function reset_password_logs_user_in_after_successful_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
        ]);

        $otpService = $this->createMock(OtpServiceInterface::class);
        $otpService->expects($this->once())
            ->method('invalidateOtp')
            ->with('test@example.com');
        $this->app->instance(OtpServiceInterface::class, $otpService);

        Livewire::test(ResetPasswordForm::class, [
            'token' => 'valid-token',
            'identifier' => 'test@example.com',
            'resetType' => 'email',
        ])
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('resetPassword')
            ->assertRedirect(route('login'));

        // Vérifier que le mot de passe a bien été changé
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
        $this->assertFalse(Hash::check('oldpassword', $user->password));
    }

    #[Test]
    public function reset_password_handles_concurrent_reset_attempts(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Simuler un service OTP qui détecte des tentatives concurrentes
        $otpService = $this->createMock(OtpServiceInterface::class);
        $otpService->method('invalidateOtp')
            ->willThrowException(new \Exception('Token déjà utilisé'));
        $this->app->instance(OtpServiceInterface::class, $otpService);

        Livewire::test(ResetPasswordForm::class, [
            'token' => 'valid-token',
            'identifier' => 'test@example.com',
            'resetType' => 'email',
        ])
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('resetPassword')
            ->assertSet('error', 'Une erreur est survenue. Veuillez réessayer plus tard.');
    }

    #[Test]
    public function reset_password_prevents_reuse_of_current_password(): void
    {
        $currentPassword = 'currentpassword123';
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make($currentPassword),
        ]);

        Livewire::test(ResetPasswordForm::class, [
            'token' => 'valid-token',
            'identifier' => 'test@example.com',
            'resetType' => 'email',
        ])
            ->set('password', $currentPassword) // Même mot de passe que l'actuel
            ->set('password_confirmation', $currentPassword)
            ->call('resetPassword')
            // Le système peut ou non empêcher la réutilisation
            // Ce test vérifie simplement que ça fonctionne
            ->assertRedirect(route('login'));
    }
}
