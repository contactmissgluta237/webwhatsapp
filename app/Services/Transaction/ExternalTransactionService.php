<?php

namespace App\Services\Transaction;

use App\DTOs\Transaction\CreateAdminRechargeDTO;
use App\DTOs\Transaction\CreateAdminWithdrawalDTO;
use App\DTOs\Transaction\CreateCustomerRechargeDTO;
use App\DTOs\Transaction\CreateCustomerWithdrawalDTO;
use App\Enums\ExternalTransactionType;
use App\Enums\TransactionMode;
use App\Enums\TransactionStatus;
use App\Events\AdminRechargeCreatedEvent;
use App\Events\AdminWithdrawalCreatedEvent;
use App\Events\ExternalTransactionApprovedEvent;
use App\Events\WithdrawalRequestedEvent;
use App\Models\ExternalTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExternalTransactionService
{
    public function createRechargeByAdmin(CreateAdminRechargeDTO $dto): ExternalTransaction
    {
        return DB::transaction(function () use ($dto) {
            /** @var User */
            $customer = User::findOrFail($dto->customer_id);

            if (! $customer->isCustomer()) {
                throw new \InvalidArgumentException('L\'utilisateur sélectionné n\'est pas un client.');
            }

            $transactionData = [
                'wallet_id' => $customer->wallet->id,
                'transaction_type' => ExternalTransactionType::RECHARGE(),
                'mode' => $dto->mode, // Assuming DTO now has a mode property
                'amount' => $dto->amount,
                'external_transaction_id' => $dto->external_transaction_id,
                'description' => $dto->description,
                'payment_method' => $dto->payment_method,
                'sender_name' => $dto->sender_name,
                'sender_account' => $dto->sender_account,
                'receiver_name' => $dto->receiver_name,
                'receiver_account' => $dto->receiver_account,
                'created_by' => $dto->created_by ?? Auth::user()?->id,
            ];

            if ($dto->mode === TransactionMode::MANUAL()) {
                $transactionData['status'] = TransactionStatus::COMPLETED();
                $transactionData['completed_at'] = now();
            } else {
                $transactionData['status'] = TransactionStatus::PENDING();
            }

            $transaction = ExternalTransaction::create($transactionData);

            if ($dto->mode === TransactionMode::MANUAL()) {
                $customer->wallet->increment('balance', $dto->amount);
                AdminRechargeCreatedEvent::dispatch($transaction);
            }

            return $transaction;
        });
    }

    public function createRechargeByCustomer(CreateCustomerRechargeDTO $dto): ExternalTransaction
    {
        return DB::transaction(function () use ($dto) {
            /** @var User */
            $user = User::findOrFail($dto->user_id);

            if (! $user->isCustomer()) {
                throw new \InvalidArgumentException('Vous devez être un client pour effectuer cette action.');
            }

            $description = "Recharge compte {$user->full_name} - ".number_format($dto->amount, 0, ',', ' ').' FCFA';

            $transaction = ExternalTransaction::create([
                'wallet_id' => $user->wallet->id,
                'transaction_type' => ExternalTransactionType::RECHARGE(),
                'mode' => TransactionMode::AUTOMATIC(),
                'status' => TransactionStatus::PENDING(),
                'amount' => $dto->amount,
                'external_transaction_id' => $dto->payment_method->prefix().'_'.uniqid(),
                'description' => $description,
                'payment_method' => $dto->payment_method,
                'sender_name' => $user->full_name,
                'sender_account' => $dto->sender_account,
                'receiver_name' => 'Système de recharge',
                'receiver_account' => 'AUTO_RECHARGE',
                'created_by' => $dto->created_by ?? Auth::user()?->id,
            ]);

            return $transaction;
        });
    }

    public function createWithdrawalByCustomer(CreateCustomerWithdrawalDTO $dto): ExternalTransaction
    {
        return DB::transaction(function () use ($dto) {
            /** @var User */
            $user = User::findOrFail($dto->user_id);

            if (! $user->isCustomer()) {
                throw new \InvalidArgumentException('Vous devez être un client pour effectuer cette action.');
            }

            /** @var Wallet */
            $wallet = $user->wallet;

            if (! $wallet || $wallet->balance < $dto->amount) {
                throw new \InvalidArgumentException('Solde insuffisant pour effectuer ce retrait.');
            }

            $description = "Retrait sur compte {$user->full_name} - ".number_format($dto->amount, 0, ',', ' ').' FCFA';

            $transaction = ExternalTransaction::create([
                'wallet_id' => $wallet->id,
                'transaction_type' => ExternalTransactionType::WITHDRAWAL(),
                'mode' => TransactionMode::AUTOMATIC(),
                'status' => TransactionStatus::PENDING(),
                'amount' => $dto->amount,
                'external_transaction_id' => $dto->payment_method->prefix().'_'.uniqid(),
                'description' => $description,
                'payment_method' => $dto->payment_method,
                'receiver_account' => $dto->receiver_account,
                'created_by' => $dto->created_by ?? Auth::user()?->id,
            ]);

            WithdrawalRequestedEvent::dispatch($transaction);

            return $transaction;
        });
    }

    public function createWithdrawalByAdmin(CreateAdminWithdrawalDTO $dto): ExternalTransaction
    {
        return DB::transaction(function () use ($dto) {
            /** @var User */
            $customer = User::findOrFail($dto->customer_id);

            if (! $customer->isCustomer()) {
                throw new \InvalidArgumentException('L\'utilisateur sélectionné n\'est pas un client.');
            }

            /** @var Wallet */
            $wallet = $customer->wallet;

            if (! $wallet || $wallet->balance < $dto->amount) {
                throw new \InvalidArgumentException('Solde insuffisant pour effectuer ce retrait.');
            }

            $transactionData = [
                'wallet_id' => $wallet->id,
                'transaction_type' => ExternalTransactionType::WITHDRAWAL(),
                'mode' => $dto->mode,
                'status' => TransactionStatus::COMPLETED(),
                'amount' => $dto->amount,
                'payment_method' => $dto->payment_method,
                'receiver_account' => $dto->receiver_account,
                'created_by' => $dto->created_by ?? Auth::user()?->id,
                'completed_at' => now(),
            ];

            if ($dto->mode === TransactionMode::MANUAL()) {
                $transactionData['status'] = TransactionStatus::COMPLETED();
                $transactionData['external_transaction_id'] = $dto->external_transaction_id;
                $transactionData['description'] = $dto->description;
                $transactionData['sender_name'] = $dto->sender_name;
                $transactionData['sender_account'] = $dto->sender_account;
                $transactionData['receiver_name'] = $dto->receiver_name;
                $transactionData['completed_at'] = now();
            } else {
                $transactionData['status'] = TransactionStatus::PENDING();
                $transactionData['external_transaction_id'] = $dto->payment_method->prefix().'_'.uniqid();
                $transactionData['description'] = "Retrait automatique pour {$customer->full_name} - ".number_format($dto->amount, 0, ',', ' ').' FCFA';
                $transactionData['sender_name'] = $customer->full_name;
                $transactionData['sender_account'] = $wallet->id; // Using wallet ID as account number
                $transactionData['receiver_name'] = 'Système de retrait';
            }

            $transaction = ExternalTransaction::create($transactionData);

            if ($dto->mode === TransactionMode::MANUAL()) {
                $wallet->decrement('balance', $dto->amount);
                AdminWithdrawalCreatedEvent::dispatch($transaction);
            }

            return $transaction;
        });
    }

    public function approve(ExternalTransaction $transaction): ExternalTransaction
    {
        return DB::transaction(function () use ($transaction) {
            if (! $transaction->needsApproval()) {
                throw new \InvalidArgumentException('This transaction cannot be approved.');
            }

            /** @var Wallet $wallet */
            $wallet = $transaction->wallet;

            if ($wallet->balance < $transaction->amount) {
                throw new \InvalidArgumentException('Insufficient balance to approve this withdrawal.');
            }

            $wallet->decrement('balance', $transaction->amount);

            $transaction->update([
                'status' => TransactionStatus::COMPLETED(),
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'completed_at' => now(),
            ]);

            ExternalTransactionApprovedEvent::dispatch($transaction);

            return $transaction;
        });
    }
}
