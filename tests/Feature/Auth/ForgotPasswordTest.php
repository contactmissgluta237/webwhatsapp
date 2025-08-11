<?php

namespace Tests\Feature\Auth;

use App\Enums\LoginChannel;
use App\Livewire\Auth\ForgotPasswordForm;
use App\Models\User;
use App\Services\Auth\Contracts\OtpServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
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
    public function users_can_view_forgot_password_form()
    {
        $response = $this->get(route('password.request'));

        $response->assertSuccessful();
        $response->assertSeeLivewire(ForgotPasswordForm::class);
    }

    /** @test */
    public function users_can_request_password_reset_with_email()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $otpService = $this->createMock(OtpServiceInterface::class);
        $otpService->expects($this->once())
            ->method('sendOtp')
            ->with('test@example.com', LoginChannel::EMAIL(), 'password_reset');
        $this->app->instance(OtpServiceInterface::class, $otpService);

        Livewire::test(ForgotPasswordForm::class)
            ->set('email', 'test@example.com')
            ->set('resetMethod', LoginChannel::EMAIL())
            ->call('sendResetLink')
            ->assertRedirect(route('password.verify.otp', [
                'email' => 'test@example.com',
                'resetType' => 'email',
            ]));
    }

    /** @test */
    public function users_can_request_password_reset_with_phone()
    {
        $user = User::factory()->create(['phone_number' => '+237655332183']);

        $otpService = $this->createMock(OtpServiceInterface::class);
        $otpService->expects($this->once())
            ->method('sendOtp')
            ->with('+237655332183', LoginChannel::PHONE(), 'password_reset');
        $this->app->instance(OtpServiceInterface::class, $otpService);

        Livewire::test(ForgotPasswordForm::class)
            ->call('phoneUpdated', [
                'name' => 'phone_number',
                'value' => '+237655332183',
                'country_id' => 1,
                'phone_number' => '655332183',
            ])
            ->set('resetMethod', LoginChannel::PHONE())
            ->call('sendResetLink')
            ->assertRedirect(route('password.verify.otp', [
                'phoneNumber' => '+237655332183',
                'resetType' => 'phone',
            ]));
    }

    /** @test */
    public function forgot_password_fails_with_invalid_email()
    {
        Livewire::test(ForgotPasswordForm::class)
            ->set('email', 'invalid-email')
            ->set('resetMethod', LoginChannel::EMAIL())
            ->call('sendResetLink')
            ->assertHasErrors(['email']);
    }

    /** @test */
    public function forgot_password_fails_with_nonexistent_email()
    {
        Livewire::test(ForgotPasswordForm::class)
            ->set('email', 'nonexistent@example.com')
            ->set('resetMethod', LoginChannel::EMAIL())
            ->call('sendResetLink')
            ->assertHasErrors(['email']);
    }

    /** @test */
    public function forgot_password_fails_with_nonexistent_phone()
    {
        Livewire::test(ForgotPasswordForm::class)
            ->call('phoneUpdated', [
                'name' => 'phone_number',
                'value' => '+237699999999',
                'country_id' => 1,
                'phone_number' => '699999999',
            ])
            ->set('resetMethod', LoginChannel::PHONE())
            ->call('sendResetLink')
            ->assertHasErrors(['phoneNumber']);
    }

    /** @test */
    public function forgot_password_handles_service_exception()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $otpService = $this->createMock(OtpServiceInterface::class);
        $otpService->method('sendOtp')
            ->willThrowException(new \Exception('Service error'));
        $this->app->instance(OtpServiceInterface::class, $otpService);

        Livewire::test(ForgotPasswordForm::class)
            ->set('email', 'test@example.com')
            ->set('resetMethod', LoginChannel::EMAIL())
            ->call('sendResetLink')
            ->assertSet('error', 'Une erreur est survenue. Veuillez réessayer plus tard.');
    }

    /** @test */
    public function users_can_switch_between_reset_methods()
    {
        Livewire::test(ForgotPasswordForm::class)
            ->assertSet('resetMethod', LoginChannel::EMAIL())
            ->call('setResetMethod', 'phone')
            ->assertSet('resetMethod', LoginChannel::PHONE())
            ->assertSet('email', '')
            ->assertSet('phoneNumber', '');
    }

    /** @test */
    public function users_can_go_back_to_login()
    {
        Livewire::test(ForgotPasswordForm::class)
            ->call('backToLogin')
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_users_cannot_view_forgot_password_form()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('password.request'));

        $response->assertRedirect('/');
    }
}
