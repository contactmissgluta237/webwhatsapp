<?php

namespace Database\Seeders;

use App\Enums\ExternalTransactionType;
use App\Enums\PaymentMethod;
use App\Enums\TransactionMode;
use App\Enums\TransactionStatus;
use App\Models\ExternalTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExternalTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Retrieve users for transactions
        $customers = User::role('customer')->take(10)->get();
        $admins = User::role('admin')->take(3)->get();

        if ($customers->isEmpty() || $admins->isEmpty()) {
            $this->command->info('Please create users with customer and admin roles first.');

            return;
        }

        // Create 50 varied external transactions
        for ($i = 0; $i < 50; $i++) {
            $user = $customers->random();
            $admin = $admins->random();

            // Retrieve or create the user's wallet with firstOrCreate
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0]
            );

            $transactionType = collect(ExternalTransactionType::values())->random();
            $mode = collect(TransactionMode::values())->random();
            $status = collect(TransactionStatus::values())->random();
            $paymentMethod = collect(PaymentMethod::values())->random();

            $amount = match ($transactionType) {
                'recharge' => rand(5000, 100000),
                'withdrawal' => rand(1000, 50000),
                default => rand(1000, 50000),
            };

            // For withdrawals, ensure they always have an appropriate status and approval if necessary
            if ($transactionType === 'withdrawal') {
                // Withdrawals can be pending, completed, or failed
                $status = collect(['pending', 'completed', 'failed'])->random();
            }

            $transaction = ExternalTransaction::create([
                'wallet_id' => $wallet->id, // Fix: use wallet_id instead of user_id
                'amount' => $amount,
                'transaction_type' => $transactionType,
                'mode' => $mode,
                'status' => $status,
                'external_transaction_id' => $this->generateExternalId($paymentMethod),
                'description' => $this->generateDescription($transactionType, $amount, $paymentMethod),
                'payment_method' => $paymentMethod,
                'gateway_transaction_id' => $mode === 'automatic' ? 'gtw_'.Str::random(15) : null,
                'gateway_response' => $mode === 'automatic' ? [
                    'status' => $status,
                    'message' => 'Transaction processed successfully',
                    'reference' => Str::random(20),
                ] : null,

                // Created by: always filled for manual transactions, and sometimes for automatic ones
                'created_by' => ($mode === 'manual') ? $admin->id : ($i % 3 === 0 ? $admin->id : null),

                // For withdrawals: mandatory approval if completed
                'approved_by' => ($transactionType === 'withdrawal' && $status === 'completed') ? $admin->id : null,
                'approved_at' => ($transactionType === 'withdrawal' && $status === 'completed') ? now()->subDays(rand(0, 5)) : null,

                // For manual transactions
                'sender_name' => $mode === 'manual' ? $user->full_name : null,
                'sender_phone' => $mode === 'manual' ? $user->phone_number : null,
                'sender_address' => $mode === 'manual' ? $user->address : null,
                'sender_bank' => $mode === 'manual' && in_array($paymentMethod, ['bank_card', 'bank_transfer'])
                    ? collect(['Ecobank', 'UBA', 'Afriland', 'BICEC'])->random() : null,
                'sender_account' => $mode === 'manual' && in_array($paymentMethod, ['bank_card', 'bank_transfer'])
                    ? $this->generateAccountNumber() : null,

                'receiver_name' => $transactionType === 'withdrawal' ? $user->full_name : null,
                'receiver_phone' => $transactionType === 'withdrawal' ? $user->phone_number : null,
                'receiver_address' => $transactionType === 'withdrawal' ? $user->address : null,
                'receiver_bank' => $transactionType === 'withdrawal' && in_array($paymentMethod, ['bank_card', 'bank_transfer'])
                    ? collect(['Ecobank', 'UBA', 'Afriland', 'BICEC'])->random() : null,
                'receiver_account' => $transactionType === 'withdrawal' && in_array($paymentMethod, ['bank_card', 'bank_transfer'])
                    ? $this->generateAccountNumber() : null,

                'completed_at' => $status === 'completed' ? now()->subDays(rand(0, 30)) : null,
                'created_at' => now()->subDays(rand(0, 60)),
                'updated_at' => now()->subDays(rand(0, 10)),
            ]);

            $this->command->info("Transaction {$transaction->id} created for {$user->full_name}");
        }

        $this->command->info('50 external transactions created successfully!');
    }

    private function generateExternalId(string $paymentMethod): string
    {
        $prefix = match ($paymentMethod) {
            'mobile_money' => 'MM',
            'orange_money' => 'OM',
            'bank_card' => 'BC',
            'bank_transfer' => 'BT',
            'cash' => 'CASH',
            'admin_manual' => 'ADM',
            default => 'EXT',
        };

        return $prefix.'_'.Str::upper(Str::random(8)).'_'.time();
    }

    private function generateDescription(string $transactionType, int $amount, string $paymentMethod): string
    {
        $paymentLabel = PaymentMethod::from($paymentMethod)->label;

        return match ($transactionType) {
            'recharge' => "Recharge of {$amount} FCFA via {$paymentLabel}",
            'withdrawal' => "Withdrawal of {$amount} FCFA to {$paymentLabel}",
            default => "Transaction of {$amount} FCFA via {$paymentLabel}",
        };
    }

    private function getGateway(string $paymentMethod): ?string
    {
        return match ($paymentMethod) {
            'mobile_money' => 'mtn_momo',
            'orange_money' => 'orange_money',
            'bank_card' => 'stripe',
            'bank_transfer' => 'wave',
            default => null,
        };
    }

    private function generateAccountNumber(): string
    {
        return rand(10000000, 99999999).rand(100, 999);
    }
}
