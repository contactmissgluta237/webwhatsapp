<?php

namespace App\Livewire\Auth;

use App\DTOs\Customer\CreateCustomerDTO;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Customer\CustomerService;
use Livewire\Component;

class RegisterForm extends Component
{
    public $first_name = '';
    public $last_name = '';
    public $email = '';
    public $phone_number = '';
    public $password = '';
    public $password_confirmation = '';
    public $terms = false;
    public $error = null;
    public $loading = false;

    public $referral_code = '';
    public $referral_code_readonly = false;

    public $country_id = null;
    public $phone_number_only = '';
    public $full_phone_number = '';

    protected $listeners = ['phoneUpdated'];

    public function mount()
    {
        $this->country_id = 1;

        $this->referral_code = request()->get('referral_code', '');
        $this->referral_code_readonly = ! empty($this->referral_code);
    }

    public function phoneUpdated($data): void
    {
        if ($data['name'] === 'phone_number') {
            $this->country_id = $data['country_id'];
            $this->phone_number_only = $data['phone_number'];
            $this->full_phone_number = $data['value'];
            $this->phone_number = $this->full_phone_number;
        }
    }

    protected function rules()
    {
        return (new RegisterRequest)->rules();
    }

    protected function messages()
    {
        return (new RegisterRequest)->messages();
    }

    public function register(CustomerService $customerService)
    {
        $this->validate();
        $this->startLoading();

        try {
            $phoneToSave = null;
            if (! empty($this->full_phone_number) && $this->full_phone_number !== '+237') {
                $phoneToSave = $this->full_phone_number;
            }

            $dto = CreateCustomerDTO::from([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'password' => $this->password,
                'phone_number' => $phoneToSave,
                'country_id' => $this->country_id,
                'referral_code' => $this->referral_code ?: null,
                'terms' => $this->terms,
            ]);

            $customer = $customerService->create($dto);

            return $this->handleSuccessfulRegistration($customer->user);
        } catch (\Exception $e) {
            $this->handleRegistrationException($e);
        } finally {
            $this->stopLoading();
        }
    }

    public function render()
    {
        return view('livewire.auth.register-form');
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

    private function handleSuccessfulRegistration($user)
    {
        session()->flash('status', 'Votre compte a été créé. Veuillez vérifier votre email pour le code de confirmation.');

        return redirect()->route('account.activate', [
            'identifier' => $user->email,
        ]);
    }

    private function handleRegistrationException(\Exception $e): void
    {
        $this->error = 'Une erreur est survenue lors de la création du compte. Veuillez réessayer.';
        $this->password = '';
        $this->password_confirmation = '';
    }
}
