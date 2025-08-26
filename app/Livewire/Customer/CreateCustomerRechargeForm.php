<?php

namespace App\Livewire\Customer;

use App\DTOs\Transaction\CreateCustomerRechargeDTO;
use App\Enums\PaymentMethod;
use App\Models\ExternalTransaction;
use App\Models\Geography\Country;
use App\Services\CurrencyService;
use App\Services\Payment\DTOs\PaymentIdentifierRequestDTO;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Transaction\ExternalTransactionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
    public $processMessage = '';
    public $processMessageType = 'info'; // 'info', 'success', 'error'

    public $pollingTransactionId = null;
    public $pollingAttempts = 0;
    private $maxPollingAttempts = 80; // 6.5 minutes (5s * 80)

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
        $countries = Country::active()->ordered()->get();

        foreach ($countries as $country) {
            if (str_starts_with($phoneNumber, $country->phone_code)) {
                $this->country_id = $country->id;

                return;
            }
        }

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

        if (in_array($this->payment_method, [PaymentMethod::MOBILE_MONEY()->value, PaymentMethod::ORANGE_MONEY()->value])) {
            if (empty($this->phone_number)) {
                throw new \Exception('Veuillez saisir un numéro de téléphone valide.');
            }
        } elseif ($this->payment_method === PaymentMethod::BANK_CARD()->value) {
            if (! $this->card_is_valid) {
                throw new \Exception('Veuillez saisir des informations de carte valides.');
            }
        }
    }

    public function createRecharge(ExternalTransactionService $transactionService)
    {
        Log::info('🔄 CreateRecharge: Method called');

        $this->resetMessages();
        $this->loading = true;

        Log::info('🔄 CreateRecharge: Loading set to true', ['loading' => $this->loading]);

        try {
            // Validation personnalisée selon le type de paiement
            $this->validatePaymentData();

            Log::info('🔄 CreateRecharge: Validation passed');

            Log::info('Recharge initiated from createCustomerRechargeForm', [
                'amount' => (int) $this->amount,
                'payment_method' => $this->payment_method,
                'sender_account' => $this->sender_account,
            ]);

            $dto = new CreateCustomerRechargeDTO(
                user_id: Auth::user()->id,
                amount: (int) $this->amount,
                // amount: 10,//just for test
                payment_method: PaymentMethod::from($this->payment_method),
                sender_account: $this->sender_account,
                created_by: Auth::user()->id,
            );

            $transaction = $transactionService->createRechargeByCustomer($dto);
            Log::info('🔄 CreateRecharge: Transaction created', ['transaction_id' => $transaction->id]);

            $result = $this->initiatePayment($this->phone_number, $transaction);
            Log::info('🔄 CreateRecharge: Payment initiated', ['success' => $result->isSuccess()]);

            if ($result->isSuccess()) {
                $this->processMessage = $result->getUserMessageToDisplay() ?: 'Paiement en cours de traitement...';
                $this->processMessageType = 'info';
                $this->pollingAttempts = 0;
                $this->pollingTransactionId = $transaction->id;

                Log::info('🔄 CreateRecharge: Starting polling', ['loading' => $this->loading, 'transaction_id' => $transaction->id]);
                // Le polling sera géré par wire:poll dans la vue
            } else {
                $this->error = "Erreur lors de l'initiation du paiement : {$result->getUserMessageToDisplay()}";
                $this->loading = false; // Arrêter le loading en cas d'erreur
                Log::info('🔄 CreateRecharge: Payment failed, loading set to false');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->error = 'Erreurs de validation : '.implode(', ', $e->validator->errors()->all());
            $this->loading = false;
            Log::error('🔄 CreateRecharge: Validation exception', ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->error = 'Erreur : '.$e->getMessage();
            $this->loading = false;
            Log::error('🔄 CreateRecharge: General exception', ['error' => $e->getMessage()]);
        }
    }

    private function initiatePayment(string $phoneNumber, ExternalTransaction $transaction)
    {
        $gatewayFactory = app(PaymentGatewayFactory::class);
        $gateway = $gatewayFactory->fromCountry(Country::find($this->country_id));
        $paymentRequest = new PaymentIdentifierRequestDTO(
            phoneNumber: $phoneNumber,
        );

        return $gateway->initiatePayment($transaction, $paymentRequest);
    }

    public function checkTransactionStatus(): void
    {
        if (! $this->pollingTransactionId) {
            return;
        }

        Log::info('🔄 CheckTransactionStatus: Checking transaction', [
            'transaction_id' => $this->pollingTransactionId,
            'attempts' => $this->pollingAttempts,
            'loading' => $this->loading,
        ]);

        // Vérifier le timeout
        if ($this->pollingAttempts >= $this->maxPollingAttempts) {
            $this->loading = false;
            $this->success = ''; // Maintenant on peut effacer le message initial
            $this->error = 'Délai dépassé. Veuillez vérifier votre compte ou réessayer plus tard.';
            $this->pollingTransactionId = null;
            Log::info('🔄 CheckTransactionStatus: Timeout reached, loading set to false');

            return;
        }

        $transaction = ExternalTransaction::find($this->pollingTransactionId);

        if (! $transaction) {
            $this->loading = false;
            $this->error = 'Transaction introuvable.';
            $this->pollingTransactionId = null;
            Log::error('🔄 CheckTransactionStatus: Transaction not found');

            return;
        }

        Log::info('🔄 CheckTransactionStatus: Transaction status', ['status' => $transaction->status->value]);

        if ($transaction->isPending()) {
            $this->pollingAttempts++;
            Log::info('🔄 CheckTransactionStatus: Still pending', ['attempts' => $this->pollingAttempts]);

            return; // Continue polling with wire:poll
        }

        // Transaction is complete
        $this->loading = false;
        $this->pollingTransactionId = null;
        Log::info('🔄 CheckTransactionStatus: Final status reached, loading set to false');
        $this->handleFinalResult($transaction);
    }

    private function handleFinalResult(ExternalTransaction $transaction): void
    {
        if ($transaction->isCompleted()) {
            $formattedAmount = $this->formatPrice($transaction->amount);
            $this->processMessage = "🎉 Félicitations ! Votre compte a été crédité de {$formattedAmount}.";
            $this->processMessageType = 'success';
        } else {
            $this->processMessage = 'Une erreur est survenue lors du paiement. Veuillez réessayer plus tard.';
            $this->processMessageType = 'error';
        }
    }

    public function resetMessages()
    {
        $this->success = '';
        $this->error = '';
        $this->processMessage = '';
    }

    public function resetForm()
    {
        $this->amount = '';
        $this->payment_method = '';
        $this->feeAmount = null;
        $this->totalToPay = null;
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
