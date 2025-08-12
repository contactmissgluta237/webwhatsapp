<?php

namespace App\Livewire\Admin\SystemAccounts\Forms;

use App\Enums\ExternalTransactionType;
use App\Enums\PaymentMethod;
use App\Http\Requests\Admin\SystemAccounts\WithdrawalRequest;
use App\Models\SystemAccount;
use App\Models\SystemAccountTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SystemAccountWithdrawalForm extends Component
{
    public $paymentMethod = '';
    public $amount;
    public $receiverName;
    public $receiverAccount;
    public $description;

    protected function customRequest(): FormRequest
    {
        return new WithdrawalRequest;
    }

    public function rules(): array
    {
        // @phpstan-ignore-next-line
        return $this->customRequest()->rules();
    }

    public function messages(): array
    {
        return $this->customRequest()->messages();
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
