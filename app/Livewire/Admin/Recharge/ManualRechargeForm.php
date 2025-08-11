<?php

namespace App\Livewire\Admin\Recharge;

use App\DTOs\Transaction\CreateAdminRechargeDTO;
use App\Enums\PaymentMethod;
use App\Enums\TransactionMode;
use App\Events\RechargeCompletedByAdminEvent;
use App\Http\Requests\Admin\CreateAdminRechargeRequest;
use App\Services\Transaction\ExternalTransactionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ManualRechargeForm extends Component
{
    public $customer_id;
    public $amount;
    public $external_transaction_id = '';
    public $description = '';
    public $payment_method = '';
    public $sender_name = '';
    public $sender_account = '';
    public $receiver_name = '';
    public $receiver_account = '';

    public $success = '';
    public $error = '';

    protected $listeners = [
        'phoneUpdated' => 'handlePhoneUpdated',
        'cardUpdated' => 'handleCardUpdated',
    ];

    public function handlePhoneUpdated($data)
    {
        if ($data['name'] === 'sender_account') {
            $this->sender_account = $data['value'];
        } elseif ($data['name'] === 'receiver_account') {
            $this->receiver_account = $data['value'];
        }
    }

    public function handleCardUpdated($data)
    {
        if ($data['name'] === 'sender_account') {
            $this->sender_account = $data['masked_card_number'];
        } elseif ($data['name'] === 'receiver_account') {
            $this->receiver_account = $data['masked_card_number'];
        }
    }

    public function mount($customer_id, $amount)
    {
        $this->customer_id = $customer_id;
        $this->amount = $amount;
    }

    public function createRecharge(ExternalTransactionService $transactionService): void
    {
        $this->resetMessages();

        try {
            $authenticatedUser = Auth::user();

            if (! $authenticatedUser) {
                Log::error('ManualRechargeForm: Tentative de création de recharge sans utilisateur authentifié', [
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'timestamp' => now()->toISOString(),
                ]);

                $this->error = 'Erreur d\'authentification : Vous devez être connecté pour effectuer cette action.';

                return;
            }

            $data = [
                'customer_id' => $this->customer_id,
                'amount' => $this->amount,
                'external_transaction_id' => $this->external_transaction_id,
                'description' => $this->description,
                'payment_method' => $this->payment_method,
                'sender_name' => $this->sender_name,
                'sender_account' => $this->sender_account,
                'receiver_name' => $this->receiver_name,
                'receiver_account' => $this->receiver_account,
            ];

            Log::info('ManualRechargeForm: Tentative de création de recharge', [
                'admin_id' => $authenticatedUser->id,
                'admin_email' => $authenticatedUser->email,
                'customer_id' => $this->customer_id,
                'amount' => $this->amount,
                'payment_method' => $this->payment_method,
                'sender_account_livewire' => $this->sender_account, // Diagnostic log
                'timestamp' => now()->toISOString(),
            ]);

            $request = new CreateAdminRechargeRequest;
            $validator = Validator::make($data, $request->rules(), $request->messages());

            if ($validator->fails()) {
                Log::warning('ManualRechargeForm: Erreurs de validation', [
                    'admin_id' => $authenticatedUser->id,
                    'errors' => $validator->errors()->toArray(),
                    'data_submitted' => $data,
                    'timestamp' => now()->toISOString(),
                ]);

                throw new ValidationException($validator);
            }

            $validated = $validator->validated();

            $dto = new CreateAdminRechargeDTO(
                customer_id: (int) $validated['customer_id'],
                amount: (int) $validated['amount'],
                external_transaction_id: $validated['external_transaction_id'],
                description: $validated['description'],
                payment_method: PaymentMethod::from($validated['payment_method']),
                sender_name: $validated['sender_name'],
                sender_account: $validated['sender_account'],
                receiver_name: $validated['receiver_name'],
                receiver_account: $validated['receiver_account'],
                created_by: $authenticatedUser->id,
                mode: TransactionMode::MANUAL()
            );

            $transaction = $transactionService->createRechargeByAdmin($dto);

            Log::info('ManualRechargeForm: Recharge créée avec succès', [
                'admin_id' => $authenticatedUser->id,
                'transaction_id' => $transaction->id,
                'external_transaction_id' => $transaction->external_transaction_id,
                'customer_id' => $transaction->wallet->user_id,
                'amount' => $transaction->amount,
                'timestamp' => now()->toISOString(),
            ]);

            $this->dispatch('rechargeCreated', "Recharge créée avec succès ! ID: {$transaction->external_transaction_id}");
            event(new RechargeCompletedByAdminEvent($transaction));

        } catch (ValidationException $e) {
            $this->error = 'Erreurs de validation : '.implode(', ', $e->validator->errors()->all());
        } catch (\Exception $e) {
            Log::error('ManualRechargeForm: Erreur lors de la création de recharge', [
                'admin_id' => $authenticatedUser->id ?? 'unknown',
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'data_submitted' => $data ?? [],
                'timestamp' => now()->toISOString(),
            ]);

            $this->error = 'Erreur : '.$e->getMessage();
        }
    }

    public function resetMessages()
    {
        $this->success = '';
        $this->error = '';
    }

    public function resetForm()
    {
        $this->external_transaction_id = '';
        $this->description = '';
        $this->payment_method = '';
        $this->sender_name = '';
        $this->sender_account = '';
        $this->receiver_name = '';
        $this->receiver_account = '';
    }

    public function render()
    {
        $paymentMethods = collect(PaymentMethod::cases())->map(function ($method) {
            return [
                'value' => $method->value,
                'label' => $method->label,
            ];
        })->toArray();

        return view('livewire.admin.recharge.manual-recharge-form', [
            'paymentMethods' => $paymentMethods,
        ]);
    }
}
