<?php

namespace App\Livewire\Auth;

use App\Enums\LoginChannel;
use App\Exceptions\UserNotFoundException;
use App\Http\Requests\Auth\ResetPasswordFormRequest;
use App\Models\User;
use App\Services\Auth\Contracts\OtpServiceInterface;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class ResetPasswordForm extends Component
{
    public string $token;
    public string $identifier;
    public string $resetType;
    public string $password = '';
    public string $password_confirmation = '';
    public ?string $error = null;
    public bool $loading = false;

    protected OtpServiceInterface $otpService;

    public function boot(OtpServiceInterface $otpService)
    {
        $this->otpService = $otpService;
    }

    public function mount(string $token, string $identifier, string $resetType = 'email')
    {
        $this->token = $token;
        $this->identifier = $identifier;
        $this->resetType = $resetType;
    }

    public function isEmailReset(): bool
    {
        return $this->resetType === LoginChannel::EMAIL()->value;
    }

    public function getIdentifierLabel(): string
    {
        return $this->isEmailReset() ? 'Adresse email' : 'Numéro de téléphone';
    }

    public function getIdentifierInputType(): string
    {
        return $this->isEmailReset() ? 'email' : 'text';
    }

    protected function rules()
    {
        return ResetPasswordFormRequest::getRulesForResetType($this->resetType);
    }

    protected function messages()
    {
        return ResetPasswordFormRequest::getMessages();
    }

    public function resetPassword()
    {
        $this->validate();
        $this->startLoading();

        try {
            $user = User::findByEmailOrPhone($this->identifier);

            if (! $user) {
                throw new UserNotFoundException($this->identifier);
            }

            $user->forceFill([
                'password' => Hash::make($this->password),
            ])->save();

            $this->otpService->invalidateOtp($this->identifier);

            session()->flash('success', 'Votre mot de passe a été réinitialisé avec succès !');

            return redirect()->route('login');

        } catch (UserNotFoundException $e) {
            $this->error = $e->getMessage();
        } catch (\Exception $e) {
            $this->error = 'Une erreur est survenue. Veuillez réessayer plus tard.';
        } finally {
            $this->stopLoading();
        }
    }

    public function render()
    {
        return view('livewire.auth.reset-password-form', [
            'resetType' => LoginChannel::from($this->resetType),
        ]);
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
