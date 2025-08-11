<?php

namespace App\Livewire\Auth;

use App\Enums\LoginChannel;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\Auth\Contracts\OtpServiceInterface;
use Livewire\Component;

class VerifyOtpForm extends Component
{
    public $identifier = '';
    public $resetType = 'email';
    public $otpCode = '';
    public $error = null;
    public $loading = false;
    public string $verificationType = 'password_reset';

    protected OtpServiceInterface $otpService;

    public function boot(OtpServiceInterface $otpService)
    {
        $this->otpService = $otpService;
    }

    public function mount($identifier = null, $resetType = 'email', $verificationType = null)
    {
        $this->identifier = $identifier ?? request('identifier', '');
        $this->resetType = $resetType ?? request('resetType', 'email');

        if (is_string($verificationType)) {
            $this->verificationType = $verificationType;
        } else {
            $this->verificationType = request('verificationType', 'password_reset');
        }

        if (empty($this->identifier)) {
            $route = $this->verificationType === 'register' ? 'register' : 'password.request';

            return redirect()->route($route);
        }
    }

    protected function rules()
    {
        return (new VerifyOtpRequest)->rules();
    }

    protected function messages()
    {
        return (new VerifyOtpRequest)->messages();
    }

    public function verifyOtp()
    {
        $this->validate();
        $this->startLoading();

        try {
            $isValid = $this->otpService->verifyOtp(
                $this->identifier,
                $this->otpCode,
                LoginChannel::make($this->resetType)
            );

            if ($isValid) {
                if ($this->verificationType === 'register') {
                    return $this->handleRegistrationVerification();
                }

                return $this->handlePasswordResetVerification();
            } else {
                $this->error = 'Code de vérification incorrect. Veuillez réessayer.';
            }
        } catch (\Exception $e) {
            $this->error = 'Une erreur est survenue lors de la vérification. Veuillez réessayer.';
        } finally {
            $this->stopLoading();
        }
    }

    public function resendOtp()
    {
        $this->startLoading();

        try {
            $this->otpService->sendOtp($this->identifier, LoginChannel::make($this->resetType));

            session()->flash('status', $this->resetType === 'email'
                ? 'Un nouveau code a été envoyé à votre adresse email.'
                : 'Un nouveau code a été envoyé à votre numéro de téléphone.');

            $this->otpCode = '';
            $this->error = null;
        } catch (\Exception $e) {
            $this->error = 'Impossible de renvoyer le code. Veuillez réessayer plus tard.';
        } finally {
            $this->stopLoading();
        }
    }

    public function backToForgotPassword()
    {
        return redirect()->route('password.request');
    }

    public function render()
    {
        return view('livewire.auth.verify-otp-form');
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

    private function handleRegistrationVerification()
    {
        $user = User::where('email', $this->identifier)->first();
        if ($user && ! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $this->otpService->invalidateOtp($this->identifier);
            session()->flash('success', 'Votre compte a été activé avec succès. Vous pouvez maintenant vous connecter.');
        }

        return redirect()->route('login');
    }

    private function handlePasswordResetVerification()
    {
        $token = $this->otpService->generateResetToken($this->identifier);

        if ($this->resetType === 'email') {
            return redirect()->route('password.reset', [
                'token' => $token,
                'email' => $this->identifier,
            ]);
        } else {
            return redirect()->route('password.reset.phone', [
                'token' => $token,
                'identifier' => $this->identifier,
                'resetType' => $this->resetType,
            ]);
        }
    }
}
