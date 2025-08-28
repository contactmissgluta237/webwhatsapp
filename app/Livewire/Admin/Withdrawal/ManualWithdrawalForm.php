<?php

namespace App\Livewire\Admin\Withdrawal;

use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Http\Requests\Admin\Withdrawal\ManualWithdrawalRequest;
use App\Models\User;
use Livewire\Component;

class ManualWithdrawalForm extends Component
{
    public $customer_id = '';
    public $amount = '';
    public $payment_method = '';
    public $receiver_account = '';
    public $external_transaction_id = '';
    public $description = '';
    public $sender_name = '';
    public $sender_account = '';
    public $receiver_name = '';

    public $phone_number = '';
    public $country_id = null;
    public $card_number = '';
    public $masked_card_number = '';
    public $expiry_month = '';
    public $expiry_year = '';
    public $card_is_valid = false;

    public $customer_wallet_balance = null;

    public $feeAmount = null;
    public $finalAmount = null;

    protected $listeners = ['phoneUpdated', 'cardUpdated', 'withdrawalCreated' => 'resetForm'];

    public function updatedAmount($value)
    {
        $amount = (float) $value;
        if ($amount > 0) {
            $feePercentage = config('system_settings.fees.withdrawal');
            $this->feeAmount = ($amount * $feePercentage) / 100;
            $this->finalAmount = $amount - $this->feeAmount;
        } else {
            $this->feeAmount = null;
            $this->finalAmount = null;
        }
    }

    public function updatedCustomerId($value)
    {
        $this->customer_wallet_balance = $this->getCustomerBalance($value);
    }

    public function updatedPaymentMethod()
    {
        $this->resetPaymentData();
    }

    public function phoneUpdated($data)
    {
        $this->handlePhoneUpdate($data);
    }

    public function cardUpdated($data)
    {
        $this->handleCardUpdate($data);
    }

    public function submitWithdrawal()
    {
        $this->validateForm();

        $data = $this->getFormData();
        $this->dispatch('withdrawalRequested', $data)->to('admin.create-admin-withdrawal-form');
    }

    private function getCustomerBalance($customerId): ?float
    {
        if (! $customerId) {
            return null;
        }

        $customer = User::find($customerId);

        return $customer?->wallet?->balance ?? 0;
    }

    private function resetPaymentData(): void
    {
        $this->receiver_account = '';
        $this->phone_number = '';
        $this->country_id = null;
        $this->card_number = '';
        $this->masked_card_number = '';
        $this->expiry_month = '';
        $this->expiry_year = '';
        $this->card_is_valid = false;
    }

    private function handlePhoneUpdate(array $data): void
    {
        if ($data['name'] === 'receiver_account') {
            $this->phone_number = $data['phone_number'];
            $this->country_id = $data['country_id'];
            $this->receiver_account = $data['value'];
        } elseif ($data['name'] === 'sender_account') {
            $this->sender_account = $data['value'];
        }
    }

    private function handleCardUpdate(array $data): void
    {
        if ($data['name'] === 'receiver_account') {
            $this->card_number = $data['card_number'];
            $this->masked_card_number = $data['masked_card_number'];
            $this->expiry_month = $data['expiry_month'];
            $this->expiry_year = $data['expiry_year'];
            $this->card_is_valid = $data['is_valid'];
            $this->receiver_account = $this->masked_card_number;
        } elseif ($data['name'] === 'sender_account') {
            $this->sender_account = $data['masked_card_number'] ?? $data['value'];
        }
    }

    private function validateForm(): void
    {
        $request = new ManualWithdrawalRequest;
        $this->validate($request->rules(), $request->messages());
        $this->validatePaymentMethodSpecificData();
    }

    private function validatePaymentMethodSpecificData(): void
    {
        if ($this->isMobilePayment()) {
            if (empty($this->phone_number)) {
                throw new \Exception('Veuillez saisir un numéro de téléphone valide.');
            }
        } elseif ($this->isBankCardPayment()) {
            if (! $this->card_is_valid) {
                throw new \Exception('Veuillez saisir des informations de carte valides.');
            }
        }
    }

    private function isMobilePayment(): bool
    {
        return in_array($this->payment_method, [
            PaymentMethod::MOBILE_MONEY()->value,
            PaymentMethod::ORANGE_MONEY()->value,
        ]);
    }

    private function isBankCardPayment(): bool
    {
        return $this->payment_method === PaymentMethod::BANK_CARD()->value;
    }

    private function getFormData(): array
    {
        return [
            'customer_id' => $this->customer_id,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'receiver_account' => $this->receiver_account,
            'external_transaction_id' => $this->external_transaction_id,
            'description' => $this->description,
            'sender_name' => $this->sender_name,
            'sender_account' => $this->sender_account,
            'receiver_name' => $this->receiver_name,
        ];
    }

    public function resetForm(): void
    {
        $this->reset([
            'customer_id', 'amount', 'payment_method', 'external_transaction_id',
            'description', 'sender_name', 'sender_account', 'receiver_name',
        ]);
        $this->resetPaymentData();
        $this->customer_wallet_balance = null;
    }

    public function render()
    {
        return view('livewire.admin.withdrawal.manual-withdrawal-form', [
            'customers' => $this->getCustomers(),
            'predefinedAmounts' => $this->getPredefinedAmounts(),
            'paymentMethods' => $this->getPaymentMethods(),
        ]);
    }

    private function getCustomers()
    {
        return User::whereHas('roles', function ($query) {
            $query->where('name', UserRole::CUSTOMER()->value);
        })->get(['id', 'first_name', 'last_name', 'email']);
    }

    private function getPredefinedAmounts(): array
    {
        return config('system_settings.predefined_amounts');
    }

    private function getPaymentMethods(): array
    {
        return collect(PaymentMethod::cases())->map(function ($method) {
            return [
                'value' => $method->value,
                'label' => $method->label,
            ];
        })->toArray();
    }
}
