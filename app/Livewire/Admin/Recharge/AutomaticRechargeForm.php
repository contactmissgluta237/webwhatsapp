<?php

namespace App\Livewire\Admin\Recharge;

use App\DTOs\Transaction\CreateAdminRechargeDTO;
use App\Enums\PaymentMethod;
use App\Enums\TransactionMode;
use App\Events\RechargeCompletedByAdminEvent;
use App\Services\Transaction\ExternalTransactionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AutomaticRechargeForm extends Component
{
    public $customer_id;
    public $amount;
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

    public $success = '';
    public $error = '';
    public $loading = false;

    protected $listeners = ['phoneUpdated', 'cardUpdated'];

    public function mount($customer_id, $amount)
    {
        Log::info('AutomaticRechargeForm: mount method called.', ['customer_id' => $customer_id, 'amount' => $amount]);
        $this->customer_id = $customer_id;
        $this->amount = $amount;
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
        Log::info('AutomaticRechargeForm: createRecharge method called.');
        $this->resetMessages();
        $this->loading = true;

        try {
            // Validation personnalisée selon le type de paiement
            $this->validatePaymentData();
            Log::info('AutomaticRechargeForm: Payment data validated.');

            $validated = [
                'amount' => (int) $this->amount,
                'payment_method' => $this->payment_method,
                'sender_account' => $this->sender_account,
            ];
            Log::info('AutomaticRechargeForm: Validated data.', $validated);

            $dto = new CreateAdminRechargeDTO(
                customer_id: (int) $this->customer_id,
                amount: (int) $validated['amount'],
                payment_method: PaymentMethod::from($validated['payment_method']),
                sender_account: $validated['sender_account'],
                external_transaction_id: '',
                description: '',
                sender_name: '',
                receiver_name: '',
                receiver_account: '',
                created_by: Auth::user()->id,
                mode: TransactionMode::AUTOMATIC()
            );
            Log::info('AutomaticRechargeForm: DTO created.', (array) $dto);

            $transaction = $transactionService->createRechargeByAdmin($dto);
            Log::info('AutomaticRechargeForm: Recharge created successfully.', ['transaction_id' => $transaction->id]);

            $this->dispatch('rechargeCreated', "Recharge initié avec succès ! Votre compte sera crédité automatiquement. ID: {$transaction->external_transaction_id}");
            event(new RechargeCompletedByAdminEvent($transaction));
            Log::info('AutomaticRechargeForm: RechargeCreated event dispatched.');

        } catch (ValidationException $e) {
            Log::error('AutomaticRechargeForm: Validation Error: '.$e->getMessage(), $e->errors());
            $this->error = 'Erreurs de validation : '.implode(', ', $e->validator->errors()->all());
        } catch (\Exception $e) {
            Log::error('AutomaticRechargeForm: General Error: '.$e->getMessage());
            $this->error = 'Erreur : '.$e->getMessage();
        } finally {
            $this->loading = false;
            Log::info('AutomaticRechargeForm: Loading set to false.');
        }
    }

    public function resetMessages()
    {
        $this->success = '';
        $this->error = '';
    }

    public function resetForm()
    {
        $this->payment_method = '';
        $this->resetPaymentData();
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

        return view('livewire.admin.recharge.automatic-recharge-form', [
            'predefinedAmounts' => $predefinedAmounts,
            'paymentMethods' => $paymentMethods,
        ]);
    }
}
