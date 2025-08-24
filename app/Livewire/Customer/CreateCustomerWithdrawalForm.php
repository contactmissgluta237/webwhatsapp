<?php

namespace App\Livewire\Customer;

use App\DTOs\Transaction\CreateCustomerWithdrawalDTO;
use App\Enums\PaymentMethod;
use App\Services\CurrencyService;
use App\Services\Transaction\ExternalTransactionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CreateCustomerWithdrawalForm extends Component
{
    public $amount = '';
    public $payment_method = '';
    public $receiver_account = '';

    // Données pour le téléphone
    public $phone_number = '';
    public $country_id = null;

    // Données pour la carte
    public $card_number = '';
    public $masked_card_number = '';
    public $expiry_month = '';
    public $expiry_year = '';
    public $card_is_valid = false;

    public $feeAmount = null;
    public $finalAmount = null;

    public $success = '';
    public $error = '';
    public $loading = false;

    protected $listeners = ['phoneUpdated', 'cardUpdated'];

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

    public function phoneUpdated($data)
    {
        if ($data['name'] === 'receiver_account') {
            $this->phone_number = $data['phone_number'];
            $this->country_id = $data['country_id'];
            $this->receiver_account = $data['value']; // Le numéro complet avec indicatif
        }
    }

    public function cardUpdated($data)
    {
        if ($data['name'] === 'receiver_account') {
            $this->card_number = $data['card_number'];
            $this->masked_card_number = $data['masked_card_number'];
            $this->expiry_month = $data['expiry_month'];
            $this->expiry_year = $data['expiry_year'];
            $this->card_is_valid = $data['is_valid'];

            // Pour les cartes, on stocke seulement le numéro masqué
            $this->receiver_account = $this->masked_card_number;
        }
    }

    public function updatedPaymentMethod()
    {
        // Réinitialiser les données quand on change de méthode de paiement
        $this->resetPaymentData();
    }

    private function resetPaymentData()
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

    private function validatePaymentData()
    {
        // Validation des champs requis
        if (empty($this->amount)) {
            throw new \Exception('Veuillez sélectionner un montant.');
        }

        if (empty($this->payment_method)) {
            throw new \Exception('Veuillez sélectionner une méthode de paiement.');
        }

        if (empty($this->receiver_account)) {
            throw new \Exception('Les informations de paiement sont requises.');
        }

        // Validation spécifique selon le type de paiement
        if (in_array($this->payment_method, [PaymentMethod::MOBILE_MONEY()->value, PaymentMethod::ORANGE_MONEY()->value])) {
            // Pour les paiements mobiles, vérifier que le numéro est complet
            if (empty($this->phone_number)) {
                throw new \Exception('Veuillez saisir un numéro de téléphone valide.');
            }
        } elseif ($this->payment_method === PaymentMethod::BANK_CARD()->value) {
            // Pour les cartes, vérifier que la validation a été faite
            if (! $this->card_is_valid) {
                throw new \Exception('Veuillez saisir des informations de carte valides.');
            }
        }
    }

    public function createWithdrawal(ExternalTransactionService $transactionService)
    {
        $this->resetMessages();
        $this->loading = true;

        try {
            // Validation personnalisée selon le type de paiement
            $this->validatePaymentData();

            $validated = [
                'amount' => (int) $this->amount,
                'payment_method' => $this->payment_method,
                'receiver_account' => $this->receiver_account,
            ];

            $dto = new CreateCustomerWithdrawalDTO(
                user_id: Auth::user()->id,
                amount: (int) $validated['amount'],
                payment_method: PaymentMethod::from($validated['payment_method']),
                receiver_account: $validated['receiver_account'],
                created_by: Auth::user()->id
            );

            $transaction = $transactionService->createWithdrawalByCustomer($dto);

            $this->success = "Demande de retrait initiée avec succès ! Vous serez notifié après approbation. ID: {$transaction->external_transaction_id}";
            $this->resetForm();

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->error = 'Erreurs de validation : '.implode(', ', $e->validator->errors()->all());
        } catch (\Exception $e) {
            $this->error = 'Erreur : '.$e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function resetMessages()
    {
        $this->success = '';
        $this->error = '';
    }

    public function resetForm()
    {
        $this->amount = '';
        $this->payment_method = '';
        $this->resetPaymentData();
    }

    public function getUserCurrency()
    {
        $currencyService = app(CurrencyService::class);

        return $currencyService->getUserCurrency(Auth::user());
    }

    public function formatPrice($amount)
    {
        $currencyService = app(CurrencyService::class);
        $currency = $this->getUserCurrency();

        return $currencyService->formatPrice($amount, $currency);
    }

    public function render()
    {
        $predefinedAmounts = config('system_settings.predefined_amounts');

        $paymentMethods = collect(PaymentMethod::cases())->map(function ($method) {
            return [
                'value' => $method->value,
                'label' => $method->label,
            ];
        })->toArray();

        return view('livewire.customer.create-customer-withdrawal-form', [
            'predefinedAmounts' => $predefinedAmounts,
            'paymentMethods' => $paymentMethods,
        ]);
    }
}
