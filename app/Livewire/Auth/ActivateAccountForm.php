<?php

namespace App\Livewire\Auth;

use App\Http\Requests\Auth\ActivateAccountFormRequest;
use App\Models\User;
use App\Services\Auth\Contracts\AccountActivationServiceInterface;
use Livewire\Component;

class ActivateAccountForm extends Component
{
    public $identifier = '';
    public $otpCode = '';
    public $error = null;
    public $loading = false;

    protected AccountActivationServiceInterface $activationService;

    public function boot(AccountActivationServiceInterface $activationService)
    {
        $this->activationService = $activationService;
    }

    public function mount($identifier = null)
    {
        $this->identifier = $identifier ?? request('identifier', '');

        if (empty($this->identifier)) {
            return redirect()->route('register');
        }
    }

    protected function rules()
    {
        return (new ActivateAccountFormRequest)->rules();
    }

    protected function messages()
    {
        return (new ActivateAccountFormRequest)->messages();
    }

    public function activateAccount()
    {
        $this->validate();
        $this->startLoading();

        try {
            $isValid = $this->activationService->verifyActivationCode($this->identifier, $this->otpCode);

            if ($isValid) {
                $user = User::where('email', $this->identifier)->first();

                if ($user && ! $user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                    session()->flash('success', 'Félicitations ! Votre compte a été activé avec succès. Vous pouvez maintenant vous connecter.');

                    return redirect()->route('login');
                }

                session()->flash('info', 'Votre compte est déjà activé. Vous pouvez vous connecter.');

                return redirect()->route('login');
            }

            $this->error = 'Code d\'activation incorrect ou expiré. Veuillez réessayer.';
        } catch (\Exception $e) {
            $this->error = 'Une erreur est survenue lors de l\'activation. Veuillez réessayer.';
        } finally {
            $this->stopLoading();
        }
    }

    public function resendActivationCode()
    {
        $this->startLoading();

        try {
            $this->activationService->sendActivationCode($this->identifier);

            session()->flash('status', 'Un nouveau code d\'activation a été envoyé à votre adresse email.');
            $this->otpCode = '';
            $this->error = null;
        } catch (\Exception $e) {
            $this->error = 'Impossible de renvoyer le code. Veuillez réessayer plus tard.';
        } finally {
            $this->stopLoading();
        }
    }

    public function render()
    {
        return view('livewire.auth.activate-account-form');
    }

    private function startLoading(): void
    {
        $this->loading = true;
        $this->error = null;
    }

    private function stopLoading(): void
    {
        $this->loading = false;
    }
}
