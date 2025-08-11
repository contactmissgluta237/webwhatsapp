<?php

namespace Tests\Feature\Auth;

use App\Enums\LoginChannel;
use App\Livewire\Auth\VerifyOtpForm;
use App\Models\User;
use App\Services\Auth\Contracts\OtpServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VerifyOtpTest extends TestCase
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
    public function users_can_view_otp_verification_form()
    {
        $response = $this->get(route('password.verify.otp', [
            'email' => 'test@example.com',
            'resetType' => 'email',
        ]));

        $response->assertSuccessful();
        $response->assertSeeLivewire(VerifyOtpForm::class);
    }

    /** @test */
    public function users_can_verify_otp_for_password_reset()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $otpService = $this->createMock(OtpServiceInterface::class);
        $otpService->expects($this->once())
            ->method('verifyOtp')
            ->with('test@example.com', '123456', LoginChannel::EMAIL())
            ->willReturn(true);
        $otpService->expects($this->once())
            ->method('generateResetToken')
            ->with('test@example.com')
            ->willReturn('reset-token-123');
        $this->app->instance(OtpServiceInterface::class, $otpService);

        Livewire::test(VerifyOtpForm::class, [
            'identifier' => 'test@example.com',
            'resetType' => 'email',
            'verificationType' => 'password_reset',
        ])
            ->set('otpCode', '123456')
            ->call('verifyOtp')
            ->assertRedirect(route('password.reset', [
                'token' => 'reset-token-123',
                'email' => 'test@example.com',
            ]));
    }

    /** @test */
    public function users_can_verify_otp_for_registration()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $otpService = $this->createMock(OtpServiceInterface::class);
        $otpService->expects($this->once())
            ->method('verifyOtp')
            ->with('test@example.com', '123456', LoginChannel::EMAIL())
            ->willReturn(true);
        $otpService->expects($this->once())
            ->method('invalidateOtp')
            ->with('test@example.com');
        $this->app->instance(OtpServiceInterface::class, $otpService);

        Livewire::test(VerifyOtpForm::class, [
            'identifier' => 'test@example.com',
            'resetType' => 'email',
            'verificationType' => 'register',
        ])
            ->set('otpCode', '123456')
            ->call('verifyOtp')
            ->assertRedirect(route('login'));

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    /** @test */
    public function otp_verification_fails_with_invalid_code()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $otpService = $this->createMock(OtpServiceInterface::class);
        $otpService->expects($this->once())
            ->method('verifyOtp')
            ->with('test@example.com', '999999', LoginChannel::EMAIL())
            ->willReturn(false);
        $this->app->instance(OtpServiceInterface::class, $otpService);

        Livewire::test(VerifyOtpForm::class, [
            'identifier' => 'test@example.com',
            'resetType' => 'email',
            'verificationType' => 'password_reset',
        ])
            ->set('otpCode', '999999')
            ->call('verifyOtp')
            ->assertSet('error', 'Code de vérification incorrect. Veuillez réessayer.');
    }

    /** @test */
    public function otp_verification_fails_with_empty_code()
    {
        Livewire::test(VerifyOtpForm::class, [
            'identifier' => 'test@example.com',
            'resetType' => 'email',
            'verificationType' => 'password_reset',
        ])
            ->set('otpCode', '')
            ->call('verifyOtp')
            ->assertHasErrors(['otpCode']);
    }

    /** @test */
    public function otp_verification_handles_service_exception()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $otpService = $this->createMock(OtpServiceInterface::class);
        $otpService->method('verifyOtp')
            ->willThrowException(new \Exception('Service error'));
        $this->app->instance(OtpServiceInterface::class, $otpService);

        Livewire::test(VerifyOtpForm::class, [
            'identifier' => 'test@example.com',
            'resetType' => 'email',
            'verificationType' => 'password_reset',
        ])
            ->set('otpCode', '123456')
            ->call('verifyOtp')
            ->assertSet('error', 'Une erreur est survenue lors de la vérification. Veuillez réessayer.');
    }

    /** @test */
    public function users_can_resend_otp()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $otpService = $this->createMock(OtpServiceInterface::class);
        $otpService->expects($this->once())
            ->method('sendOtp')
            ->with('test@example.com', LoginChannel::EMAIL());
        $this->app->instance(OtpServiceInterface::class, $otpService);

        Livewire::test(VerifyOtpForm::class, [
            'identifier' => 'test@example.com',
            'resetType' => 'email',
            'verificationType' => 'password_reset',
        ])
            ->set('otpCode', '123456') // Définir un code initial
            ->call('resendOtp')
            ->assertSet('otpCode', '') // Vérifier que le code a été réinitialisé
            ->assertSet('error', null); // Vérifier que l'erreur a été effacée
    }

    /** @test */
    public function users_can_go_back_to_forgot_password()
    {
        Livewire::test(VerifyOtpForm::class, [
            'identifier' => 'test@example.com',
            'resetType' => 'email',
            'verificationType' => 'password_reset',
        ])
            ->call('backToForgotPassword')
            ->assertRedirect(route('password.request'));
    }

    /** @test */
    public function verification_redirects_if_no_identifier()
    {
        $response = $this->get(route('password.verify.otp', [
            'resetType' => 'email',
        ]));

        $response->assertRedirect(route('password.request'));
    }

    /** @test */
    public function phone_otp_verification_works()
    {
        $user = User::factory()->create(['phone_number' => '+237655332183']);

        $otpService = $this->createMock(OtpServiceInterface::class);
        $otpService->expects($this->once())
            ->method('verifyOtp')
            ->with('+237655332183', '123456', LoginChannel::PHONE())
            ->willReturn(true);
        $otpService->expects($this->once())
            ->method('generateResetToken')
            ->with('+237655332183')
            ->willReturn('reset-token-123');
        $this->app->instance(OtpServiceInterface::class, $otpService);

        Livewire::test(VerifyOtpForm::class, [
            'identifier' => '+237655332183',
            'resetType' => 'phone',
            'verificationType' => 'password_reset',
        ])
            ->set('otpCode', '123456')
            ->call('verifyOtp')
            ->assertRedirect(route('password.reset.phone', [
                'token' => 'reset-token-123',
                'identifier' => '+237655332183',
                'resetType' => 'phone',
            ]));
    }
}
