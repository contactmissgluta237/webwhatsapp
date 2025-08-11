<?php

namespace App\Livewire\Auth;

use App\Enums\LoginChannel;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\RedirectionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class LoginForm extends Component
{
    public string $email = '';
    public string $phone_number = '';
    public string $loginMethod = 'email';

    public ?int $country_id = null;
    public string $phone_number_only = '';
    public string $full_phone_number = '';

    public string $password = '';
    public bool $remember = false;
    public ?string $error = null;
    public bool $loading = false;

    protected $listeners = ['phoneUpdated'];

    public function mount(): void
    {
        $this->loginMethod = LoginChannel::EMAIL()->value;
        $this->country_id = 1;
        $this->phone_number_only = '';
        $this->phone_number = '';
        $this->full_phone_number = '';
    }

    public function phoneUpdated(array $data): void
    {
        if ($data['name'] === 'phone_number') {
            $this->country_id = $data['country_id'];
            $this->phone_number_only = $data['phone_number'];
            $this->full_phone_number = $data['value'];
            $this->phone_number = $this->full_phone_number;
        }
    }

    public function setLoginMethod(string $method): void
    {
        $this->loginMethod = $method;
        $this->reset(['email', 'phone_number', 'error']);
    }

    protected function rules(): array
    {
        return LoginRequest::getRulesForMethod($this->loginMethod);
    }

    protected function messages(): array
    {
        return LoginRequest::getMessages();
    }

    public function login()
    {
        $this->validate();
        $this->startLoading();

        try {
            $user = $this->attemptLogin();

            if ($user) {
                Auth::login($user, $this->remember);

                if (session()->isStarted()) {
                    session()->regenerate();
                }

                return $this->handleSuccessfulLogin();
            }

            $this->handleFailedLogin();
        } catch (\Exception $e) {
            $this->handleAuthenticationException($e);
        } finally {
            $this->stopLoading();
        }
    }

    public function forgotPassword()
    {
        return redirect()->route('password.request');
    }

    public function render()
    {
        return view('livewire.auth.login-form');
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

    private function handleSuccessfulLogin()
    {
        $user = Auth::user();
        session()->flash('success', "Bienvenue {$user->full_name} !");
        $redirectionService = app(RedirectionService::class);

        $route = $redirectionService->getDashboardRoute($user);

        return $this->redirect(route($route));
    }

    private function handleFailedLogin(): void
    {
        $this->error = 'Identifiants incorrects. Veuillez réessayer.';
        $this->password = '';
    }

    private function handleAuthenticationException(\Exception $e): void
    {
        $this->error = 'Une erreur est survenue. Veuillez réessayer.';
        $this->password = '';
    }

    private function attemptLogin(): ?User
    {
        if ($this->loginMethod === LoginChannel::EMAIL()->value) {
            $user = User::where('email', $this->email)->first();
        } else {
            $user = User::where('phone_number', $this->phone_number)->first();
        }

        if ($user && Hash::check($this->password, $user->password)) {
            return $user;
        }

        return null;
    }
}
