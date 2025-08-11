<?php

namespace App\Livewire\Admin\Transactions\Forms;

use App\DTOs\Transaction\CreateAdminWithdrawalDTO;
use App\Enums\PaymentMethod;
use App\Enums\TransactionMode;
use App\Services\Transaction\ExternalTransactionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CreateAdminWithdrawalForm extends Component
{
    public $withdrawal_mode;
    public $success = '';
    public $error = '';
    public $loading = false;

    protected $listeners = ['withdrawalRequested'];

    public function mount()
    {
        $this->withdrawal_mode = TransactionMode::MANUAL()->value;
    }

    public function withdrawalRequested($data)
    {
        if ($this->loading) {
            $this->logDoubleSubmission($data);

            return;
        }

        $this->loading = true;
        $this->resetMessages();
        $this->logWithdrawalStart($data);

        try {
            $transaction = $this->processWithdrawal($data);
            $this->handleSuccess($transaction);
        } catch (\Exception $e) {
            $this->handleError($e);
        } finally {
            $this->loading = false;
            $this->logWithdrawalEnd();
        }
    }

    private function processWithdrawal(array $data): object
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $dto = $this->createDTO($data, $authenticatedUser->id);

        return app(ExternalTransactionService::class)->createWithdrawalByAdmin($dto);
    }

    private function getAuthenticatedUser(): object
    {
        $user = Auth::user();

        if (! $user) {
            throw new \Exception('Erreur d\'authentification : Vous devez être connecté pour effectuer cette action.');
        }

        return $user;
    }

    private function createDTO(array $data, int $adminId): CreateAdminWithdrawalDTO
    {
        $dtoData = [
            'customer_id' => (int) $data['customer_id'],
            'amount' => (int) $data['amount'],
            'payment_method' => PaymentMethod::from($data['payment_method']),
            'receiver_account' => $data['receiver_account'],
            'created_by' => $adminId,
            'mode' => TransactionMode::from($this->withdrawal_mode),
        ];

        if ($this->isManualMode()) {
            $dtoData = array_merge($dtoData, [
                'external_transaction_id' => $data['external_transaction_id'],
                'description' => $data['description'],
                'sender_name' => $data['sender_name'],
                'sender_account' => $data['sender_account'],
                'receiver_name' => $data['receiver_name'],
            ]);
        }

        return new CreateAdminWithdrawalDTO(...$dtoData);
    }

    private function isManualMode(): bool
    {
        return $this->withdrawal_mode === TransactionMode::MANUAL()->value;
    }

    private function handleSuccess(object $transaction): void
    {
        $this->success = "Retrait créé avec succès ! ID: {$transaction->external_transaction_id}";
        $this->dispatch('withdrawalCreated');
    }

    private function handleError(\Exception $e): void
    {
        Log::error('Withdrawal creation failed', [
            'admin_id' => Auth::user()?->id,
            'error_message' => $e->getMessage(),
            'error_trace' => $e->getTraceAsString(),
            'timestamp' => now()->toISOString(),
        ]);

        $this->error = 'Erreur : '.$e->getMessage();
    }

    private function resetMessages(): void
    {
        $this->success = '';
        $this->error = '';
    }

    private function logDoubleSubmission(array $data): void
    {
        Log::warning('Double submission blocked', [
            'admin_id' => Auth::user()?->id,
            'customer_id' => $data['customer_id'] ?? null,
            'timestamp' => now()->toISOString(),
        ]);
    }

    private function logWithdrawalStart(array $data): void
    {
        Log::info('Withdrawal creation started', [
            'admin_id' => Auth::user()?->id,
            'customer_id' => $data['customer_id'] ?? null,
            'amount' => $data['amount'] ?? null,
            'form_call_id' => uniqid('form_'),
            'timestamp' => now()->toISOString(),
        ]);
    }

    private function logWithdrawalEnd(): void
    {
        Log::info('Withdrawal creation ended', [
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function render()
    {
        return view('livewire.admin.create-admin-withdrawal-form', [
            'withdrawalModes' => $this->getWithdrawalModes(),
        ]);
    }

    private function getWithdrawalModes(): array
    {
        return collect(TransactionMode::cases())->map(function ($mode) {
            return [
                'value' => $mode->value,
                'label' => $mode->label,
            ];
        })->toArray();
    }
}
