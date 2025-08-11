<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ActivateAccountForm;
use App\Models\User;
use App\Services\Auth\Contracts\AccountActivationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function users_can_view_activation_form_with_valid_identifier()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->get(route('account.activate', ['identifier' => 'test@example.com']));

        $response->assertSuccessful();
        $response->assertSeeLivewire(ActivateAccountForm::class);
    }

    /** @test */
    public function users_can_activate_account_with_valid_code()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->expects($this->once())
            ->method('verifyActivationCode')
            ->with('test@example.com', '123456')
            ->willReturn(true);

        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        Livewire::test(ActivateAccountForm::class, ['identifier' => 'test@example.com'])
            ->set('otpCode', '123456')
            ->call('activateAccount')
            ->assertRedirect(route('login'));

        // Vérifier que l'utilisateur est maintenant vérifié
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    /** @test */
    public function activation_fails_with_invalid_code()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->expects($this->once())
            ->method('verifyActivationCode')
            ->with('test@example.com', '999999')
            ->willReturn(false);

        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        Livewire::test(ActivateAccountForm::class, ['identifier' => 'test@example.com'])
            ->set('otpCode', '999999')
            ->call('activateAccount')
            ->assertSet('error', 'Code d\'activation incorrect ou expiré. Veuillez réessayer.');

        // Vérifier que l'utilisateur n'est toujours pas vérifié
        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    /** @test */
    public function users_can_resend_activation_code()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->expects($this->once())
            ->method('sendActivationCode')
            ->with('test@example.com');

        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        // Test que la méthode s'exécute sans erreur et remet à zéro les champs appropriés
        Livewire::test(ActivateAccountForm::class, ['identifier' => 'test@example.com'])
            ->set('otpCode', '123456') // Définir une valeur initiale
            ->call('resendActivationCode')
            ->assertSet('otpCode', '') // Vérifier que le code a été réinitialisé
            ->assertSet('error', null) // Vérifier qu'il n'y a pas d'erreur
            ->assertSet('loading', false); // Vérifier que le loading est arrêté
    }

    /** @test */
    public function activation_requires_valid_otp_code_format()
    {
        Livewire::test(ActivateAccountForm::class, ['identifier' => 'test@example.com'])
            ->set('otpCode', '12') // Too short
            ->call('activateAccount')
            ->assertHasErrors(['otpCode']);
    }

    /** @test */
    public function activation_handles_service_exceptions()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $activationService = $this->createMock(AccountActivationServiceInterface::class);
        $activationService->method('verifyActivationCode')
            ->willThrowException(new \Exception('Service error'));

        $this->app->instance(AccountActivationServiceInterface::class, $activationService);

        Livewire::test(ActivateAccountForm::class, ['identifier' => 'test@example.com'])
            ->set('otpCode', '123456')
            ->call('activateAccount')
            ->assertSet('error', 'Une erreur est survenue lors de l\'activation. Veuillez réessayer.');
    }
}
