<?php

namespace App\Livewire\Admin\Transactions\Forms;

use App\Enums\TransactionMode;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CreateAdminRechargeForm extends Component
{
    public $customer_id = '';
    public $customer_wallet_balance = null;
    public $recharge_mode;
    public $amount = '';

    public $feeAmount = null;
    public $totalToPay = null;

    public $success = '';
    public $error = '';

    protected $listeners = [
        'rechargeCreated' => 'handleRechargeCreated',
    ];

    public function updatedAmount($value)
    {
        Log::info('CreateAdminRechargeForm: updatedAmount called with value: '.$value);
        $amount = (float) $value;
        if ($amount > 0) {
            $feePercentage = config('system_settings.fees.recharge');
            $this->feeAmount = ($amount * $feePercentage) / 100;
            $this->totalToPay = $amount + $this->feeAmount;
            Log::info('CreateAdminRechargeForm: Calculated feeAmount: '.$this->feeAmount.' and totalToPay: '.$this->totalToPay);
        } else {
            $this->feeAmount = null;
            $this->totalToPay = null;
            Log::info('CreateAdminRechargeForm: Amount is not positive, resetting fee and total.');
        }
    }

    public function mount()
    {
        $this->recharge_mode = TransactionMode::MANUAL()->value;
    }

    public function updatedCustomerId($value): void
    {
        if ($value) {
            $customer = User::find($value);
            $this->customer_wallet_balance = $customer && $customer->wallet ? $customer->wallet->balance : 0;
        } else {
            $this->customer_wallet_balance = null;
        }
    }

    public function setRechargeMode(string $mode): void
    {
        Log::info('CreateAdminRechargeForm: setRechargeMode called with mode: '.$mode);
        $this->recharge_mode = $mode;
        $this->resetMessages();
    }

    public function handleRechargeCreated(string $message): void
    {
        $this->success = $message;
        // Keep the success message visible, reset form on next interaction or explicit action
    }

    public function resetMessages(): void
    {
        $this->success = '';
        $this->error = '';
    }

    public function resetForm(): void
    {
        $this->customer_id = '';
        $this->customer_wallet_balance = null;
        $this->resetMessages();
    }

    public function render()
    {
        Log::info('CreateAdminRechargeForm: render method called.');
        $customers = User::whereHas('roles', function ($query) {
            $query->where('name', 'customer');
        })->get(['id', 'first_name', 'last_name', 'email']);

        $predefinedAmounts = config('system_settings.predefined_amounts');

        return view('livewire.admin.create-admin-recharge-form', [
            'customers' => $customers,
            'predefinedAmounts' => $predefinedAmounts,
            'rechargeModes' => collect(TransactionMode::cases())->map(function ($mode) {
                return [
                    'value' => $mode->value,
                    'label' => $mode->label,
                ];
            })->toArray(),
        ]);
    }
}
