<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ResetPasswordForm;
use App\Models\User;
use App\Services\Auth\Contracts\OtpServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
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

    /** @test */
    public function users_can_view_reset_password_form_with_email()
    {
        $response = $this->get(route('password.reset', [
            'token' => 'valid-token',
            'email' => 'test@example.com',
        ]));

        $response->assertSuccessful();
        $response->assertSeeLivewire(ResetPasswordForm::class);
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
    public function form_shows_correct_labels_for_email_reset()
    {
        $component = Livewire::test(ResetPasswordForm::class, [
            'token' => 'valid-token',
            'identifier' => 'test@example.com',
            'resetType' => 'email',
        ]);

        $this->assertEquals('Adresse email', $component->instance()->getIdentifierLabel());
        $this->assertEquals('email', $component->instance()->getIdentifierInputType());
        $this->assertTrue($component->instance()->isEmailReset());
    }

    /** @test */
    public function form_shows_correct_labels_for_phone_reset()
    {
        $component = Livewire::test(ResetPasswordForm::class, [
            'token' => 'valid-token',
            'identifier' => '+237655332183',
            'resetType' => 'phone',
        ]);

        $this->assertEquals('Numéro de téléphone', $component->instance()->getIdentifierLabel());
        $this->assertEquals('text', $component->instance()->getIdentifierInputType());
        $this->assertFalse($component->instance()->isEmailReset());
    }

    /** @test */
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
}
