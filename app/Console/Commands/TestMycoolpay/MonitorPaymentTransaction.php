<?php

namespace App\Console\Commands\TestMycoolpay;

use App\Models\ExternalTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorPaymentTransaction extends Command
{
    protected $signature = 'payment-mycoolpay:monitor {transactionId : Transaction ID to monitor}';

    protected $description = 'Monitor a payment transaction in real-time';

    public function handle(): int
    {
        if ($this->isProduction()) {
            $this->error('❌ Cannot run test commands in production environment!');
            Log::warning('Attempted to run MyCoolPay test command in production', [
                'command' => $this->getName(),
                'environment' => app()->environment(),
            ]);

            return Command::FAILURE;
        }

        $transactionId = $this->argument('transactionId');

        $this->info("🔍 Monitoring Transaction ID: {$transactionId}");
        $this->info("Press Ctrl+C to stop monitoring\n");

        while (true) {
            $transaction = ExternalTransaction::where('external_transaction_id', $transactionId)->first();

            if (! $transaction) {
                $this->error("❌ Transaction {$transactionId} not found");

                return self::FAILURE;
            }

            $this->displayTransactionStatus($transaction);

            if ($transaction->status === 'completed' || $transaction->status === 'failed') {
                $this->info("🏁 Transaction reached final status: {$transaction->status}");
                break;
            }

            sleep(3); // Check every 3 seconds
        }

        return self::SUCCESS;
    }

    private function displayTransactionStatus(ExternalTransaction $transaction): void
    {
        $now = now()->format('H:i:s');
        $statusIcon = match ($transaction->status) {
            'pending' => '⏳',
            'completed' => '✅',
            'failed' => '❌',
            default => '❓'
        };

        $this->line("[{$now}] {$statusIcon} Status: {$transaction->status} | Gateway Ref: {$transaction->gateway_transaction_id} | Amount: {$transaction->amount} XAF");

        Log::info('📊 TRANSACTION MONITOR', [
            'transaction_id' => $transaction->id,
            'status' => $transaction->status,
            'gateway_reference' => $transaction->gateway_transaction_id,
            'amount' => $transaction->amount,
            'updated_at' => $transaction->updated_at,
        ]);
    }

    private function isProduction(): bool
    {
        return app()->environment('production');
    }
}
