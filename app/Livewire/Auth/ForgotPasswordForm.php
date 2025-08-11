<?php

namespace App\Livewire\Auth;

use App\Enums\LoginChannel;
use App\Http\Requests\Auth\ForgotPasswordFormRequest;
use App\Services\Auth\Contracts\OtpServiceInterface;
use Livewire\Component;

class ForgotPasswordForm extends Component
{
    public $email = '';
    public $phoneNumber = '';
    public LoginChannel $resetMethod;
    public $message = null;
    public $error = null;
    public $loading = false;

    // Propriétés pour le composant phone
    public $country_id = null;
    public $phone_number_only = '';
    public $full_phone_number = '';

    protected OtpServiceInterface $otpService;
    protected $listeners = ['phoneUpdated'];

    public function boot(OtpServiceInterface $otpService)
    {
        $this->otpService = $otpService;
    }

    public function mount()
    {
        $this->resetMethod = LoginChannel::EMAIL();
        $this->country_id = 1; // Cameroun par défaut
    }

    public function phoneUpdated($data): void
    {
        if ($data['name'] === 'phone_number') {
            $this->country_id = $data['country_id'];
            $this->phone_number_only = $data['phone_number'];
            $this->full_phone_number = $data['value'];
            $this->phoneNumber = $this->full_phone_number;
        }
    }

    public function setResetMethod(string $method)
    {
        $this->resetMethod = LoginChannel::make($method);
        $this->reset(['email', 'phoneNumber', 'error', 'message']);
    }

    protected function rules()
    {
        return ForgotPasswordFormRequest::getRulesForMethod($this->resetMethod->value);
    }

    protected function messages()
    {
        return ForgotPasswordFormRequest::getMessages();
    }

    public function sendResetLink()
    {
        $this->validate();
        $this->startLoading();

        try {
            $identifier = ($this->resetMethod->equals(LoginChannel::EMAIL())) ? $this->email : $this->phoneNumber;
            $this->otpService->sendOtp($identifier, $this->resetMethod, 'password_reset');

            if ($this->resetMethod->equals(LoginChannel::EMAIL())) {
                session()->flash('status', 'Un code de réinitialisation a été envoyé à votre adresse email.');

                return redirect()->route('password.verify.otp', [
                    'email' => $this->email,
                    'resetType' => 'email',
                ]);
            } else {
                session()->flash('status', 'Un code de réinitialisation a été envoyé à votre numéro de téléphone.');

                return redirect()->route('password.verify.otp', [
                    'phoneNumber' => $this->phoneNumber,
                    'resetType' => 'phone',
                ]);
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        } finally {
            $this->stopLoading();
        }
    }

    public function backToLogin()
    {
        return redirect()->route('login');
    }

    public function render()
    {
        return view('livewire.auth.forgot-password-form');
    }

    private function startLoading(): void
    {
        $this->loading = true;
        $this->error = null;
        $this->message = null;
    }

    private function stopLoading(): void
    {
        $this->loading = false;
    }

    private function handleException(\Exception $e): void
    {
        $this->error = 'Une erreur est survenue. Veuillez réessayer plus tard.';
    }
}
