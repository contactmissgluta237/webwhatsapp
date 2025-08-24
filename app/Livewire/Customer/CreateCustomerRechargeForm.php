<?php

namespace App\Livewire\Customer;

use App\DTOs\Transaction\CreateCustomerRechargeDTO;
use App\Enums\PaymentMethod;
use App\Services\CurrencyService;
use App\Services\Transaction\ExternalTransactionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CreateCustomerRechargeForm extends Component
{
    public $amount = '';
    public $payment_method = '';
    public $sender_account = '';

    // Données pour le téléphone
    public $phone_number = '';
    public $country_id = null;

    // Données pour la carte
    public $card_number = '';
    public $masked_card_number = '';
    public $cvv = '';
    public $expiry_month = '';
    public $expiry_year = '';
    public $card_is_valid = false;

    public $feeAmount = null;
    public $totalToPay = null;

    public $success = '';
    public $error = '';
    public $loading = false;

    protected $listeners = ['phoneUpdated', 'cardUpdated'];

    public function mount()
    {
        // Préremplir le numéro de téléphone de l'utilisateur connecté
        $user = Auth::user();
        if ($user && $user->phone_number) {
            $this->sender_account = $user->phone_number;
            $this->phone_number = $user->phone_number;

            // Parser le numéro pour extraire le country_id
            $this->parseUserPhoneNumber($user->phone_number);
        }
    }

    private function parseUserPhoneNumber(string $phoneNumber): void
    {
        $countries = \App\Models\Geography\Country::active()->ordered()->get();

        foreach ($countries as $country) {
            if (str_starts_with($phoneNumber, $country->phone_code)) {
                $this->country_id = $country->id;

                return;
            }
        }

        // Si aucun pays trouvé, utiliser le Cameroun par défaut
        $defaultCountry = $countries->where('code', 'CM')->first();
        if ($defaultCountry) {
            $this->country_id = $defaultCountry->id;
        }
    }

    public function updatedAmount($value)
    {
        $amount = (float) $value;
        if ($amount > 0) {
            $feePercentage = config('system_settings.fees.recharge');
            $this->feeAmount = ($amount * $feePercentage) / 100;
            $this->totalToPay = $amount + $this->feeAmount;
        } else {
            $this->feeAmount = null;
            $this->totalToPay = null;
        }
    }

    public function phoneUpdated($data)
    {
        if ($data['name'] === 'sender_account') {
            $this->phone_number = $data['phone_number'];
            $this->country_id = $data['country_id'];
            $this->sender_account = $data['value']; // Le numéro complet avec indicatif
        }
    }

    public function cardUpdated($data)
    {
        if ($data['name'] === 'sender_account') {
            $this->card_number = $data['card_number'];
            $this->masked_card_number = $data['masked_card_number'];
            $this->cvv = $data['cvv'];
            $this->expiry_month = $data['expiry_month'];
            $this->expiry_year = $data['expiry_year'];
            $this->card_is_valid = $data['is_valid'];

            // Pour les cartes, on stocke seulement le numéro masqué
            $this->sender_account = $this->masked_card_number;
        }
    }

    public function updatedPaymentMethod()
    {
        // Réinitialiser les données quand on change de méthode de paiement
        $this->resetPaymentData();
    }

    private function resetPaymentData()
    {
        $this->sender_account = '';
        $this->phone_number = '';
        $this->country_id = null;
        $this->card_number = '';
        $this->masked_card_number = '';
        $this->cvv = '';
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

        if (empty($this->sender_account)) {
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

    public function createRecharge(ExternalTransactionService $transactionService)
    {
        $this->resetMessages();
        $this->loading = true;

        try {
            // Validation personnalisée selon le type de paiement
            $this->validatePaymentData();

            $validated = [
                'amount' => (int) $this->amount,
                'payment_method' => $this->payment_method,
                'sender_account' => $this->sender_account,
            ];

            $dto = new CreateCustomerRechargeDTO(
                user_id: Auth::user()->id,
                amount: (int) $validated['amount'],
                payment_method: PaymentMethod::from($validated['payment_method']),
                sender_account: $validated['sender_account'],
                created_by: Auth::user()->id
            );

            $transaction = $transactionService->createRechargeByCustomer($dto);

            $this->success = "Recharge initié avec succès ! Votre compte sera crédité automatiquement. ID: {$transaction->external_transaction_id}";
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

        return view('livewire.customer.create-customer-recharge-form', [
            'predefinedAmounts' => $predefinedAmounts,
            'paymentMethods' => $paymentMethods,
        ]);
    }
}
