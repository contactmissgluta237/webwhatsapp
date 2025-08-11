<?php

namespace App\Livewire\Admin\SystemAccounts\Forms;

use App\Enums\ExternalTransactionType;
use App\Enums\PaymentMethod;
use App\Http\Requests\Admin\SystemAccounts\RechargeRequest;
use App\Models\SystemAccount;
use App\Models\SystemAccountTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class SystemAccountRechargeForm extends Component
{
    public $paymentMethod = '';
    public $amount;
    public $senderName;
    public $senderAccount;
    public $description;

    protected function customRequest(): FormRequest
    {
        return new RechargeRequest();
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
        Log::info('SystemAccountRechargeForm: submit method called.');
        try {
            $this->validate();
            Log::info('SystemAccountRechargeForm: Validation passed.');

            Log::info('SystemAccountRechargeForm: Attempting to find SystemAccount for payment method: '.$this->paymentMethod);
            $systemAccount = SystemAccount::where('type', $this->paymentMethod)->firstOrFail();
            Log::info('SystemAccountRechargeForm: Found SystemAccount ID: '.$systemAccount->id.' with current balance: '.$systemAccount->balance);

            Log::info('SystemAccountRechargeForm: Creating SystemAccountTransaction.');
            SystemAccountTransaction::create([
                'system_account_id' => $systemAccount->id,
                'type' => ExternalTransactionType::RECHARGE(),
                'amount' => $this->amount,
                'sender_name' => $this->senderName,
                'sender_account' => $this->senderAccount,
                'description' => $this->description,
                'created_by' => Auth::id(),
            ]);
            Log::info('SystemAccountRechargeForm: SystemAccountTransaction created successfully.');

            Log::info('SystemAccountRechargeForm: Incrementing system account balance by: '.$this->amount);
            $systemAccount->increment('balance', $this->amount);
            Log::info('SystemAccountRechargeForm: System account balance incremented. New balance: '.$systemAccount->fresh()->balance);

            session()->flash('success', 'Recharge du compte système effectuée avec succès.');
            Log::info('SystemAccountRechargeForm: Success session flashed and form reset.');
            $this->reset();
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('SystemAccountRechargeForm: Validation failed: '.json_encode($e->errors()));
            session()->flash('error', 'Erreur de validation: Veuillez vérifier les champs.');
        } catch (\Exception $e) {
            Log::error('SystemAccountRechargeForm: Erreur lors de la recharge du compte système: '.$e->getMessage().'\nStack Trace: '.$e->getTraceAsString());
            session()->flash('error', 'Erreur lors de la recharge du compte système : '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.system-account-recharge-form', [
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }
}
