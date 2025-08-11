<?php

namespace App\Livewire\Admin\SystemAccounts\Forms;

use App\Enums\ExternalTransactionType;
use App\Enums\PaymentMethod;
use App\Models\SystemAccount;
use App\Models\SystemAccountTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SystemAccountWithdrawalForm extends Component
{
    public $paymentMethod = '';
    public $amount;
    public $receiverName;
    public $receiverAccount;
    public $description;

    protected function rules()
    {
        return [
            'paymentMethod' => ['required', Rule::in(PaymentMethod::values())],
            'amount' => 'required|numeric|min:1',
            'receiverName' => 'required|string|max:255',
            'receiverAccount' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ];
    }

    public function submit()
    {
        $this->validate();

        try {
            $systemAccount = SystemAccount::where('type', $this->paymentMethod)->firstOrFail();

            if ($systemAccount->balance < $this->amount) {
                session()->flash('error', 'Solde insuffisant sur ce compte système.');

                return;
            }

            SystemAccountTransaction::create([
                'system_account_id' => $systemAccount->id,
                'type' => ExternalTransactionType::WITHDRAWAL(),
                'amount' => $this->amount,
                'receiver_name' => $this->receiverName,
                'receiver_account' => $this->receiverAccount,
                'description' => $this->description,
                'created_by' => Auth::id(),
            ]);

            $systemAccount->decrement('balance', $this->amount);

            session()->flash('success', 'Retrait du compte système effectué avec succès.');
            $this->reset();
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors du retrait du compte système : '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.system-account-withdrawal-form', [
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }
}
