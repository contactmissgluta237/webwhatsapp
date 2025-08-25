<?php

declare(strict_types=1);

namespace App\Console\Commands\TestMycoolpay;

use App\Enums\ExternalTransactionType;
use App\Enums\PaymentMethod;
use App\Models\ExternalTransaction;
use App\Services\Payment\Gateways\MyCoolPay\Enums\MyCoolPayCurrency;
use App\Services\Payment\Gateways\MyCoolPay\Enums\MyCoolPayOperator;
use App\Services\Payment\Gateways\MyCoolPay\Enums\MyCoolPayTransactionType;
use App\Services\Payment\Gateways\MyCoolPay\MyCoolPayMappingHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class TestMyCoolPayRecharge extends Command
{
    protected $signature = 'test-mycoolpay-recharge 
                            {transaction_ref : The external transaction ID to test}
                            {--status=SUCCESS : Transaction status (SUCCESS or FAILED)}';

    protected $description = 'Test MyCoolPay webhook E2E by simulating a payment notification for an existing transaction';

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

        $transactionRef = $this->argument('transaction_ref');
        $status = strtoupper($this->option('status'));

        $this->info('=== MyCoolPay E2E Test ===');
        $this->info("Looking for transaction: {$transactionRef}");

        // Find the ExternalTransaction
        $transaction = ExternalTransaction::with(['wallet.user'])
            ->where('external_transaction_id', $transactionRef)
            ->first();

        if (! $transaction) {
            $this->error("❌ Transaction not found: {$transactionRef}");

            return Command::FAILURE;
        }

        $this->info("✓ Transaction found: ID {$transaction->id}");
        $this->info("  Type: {$transaction->transaction_type->value}");
        $this->info("  Amount: {$transaction->amount}");
        $this->info('  Payment Method: '.($transaction->payment_method?->value ?? 'Not set'));
        $this->info("  Status to test: {$status}");
        $this->newLine();

        // Generate signature using the helper
        try {
            $signature = MyCoolPayMappingHelper::generateSignature($transaction);
        } catch (\Exception $e) {
            $this->error("❌ Failed to generate signature: {$e->getMessage()}");

            return Command::FAILURE;
        }

        // Create webhook payload
        $payload = $this->buildWebhookPayload($transaction, $status, $signature);

        $webhookUrl = url('/api/payment/mycoolpay/webhook');

        $this->info('Webhook URL: '.$webhookUrl);
        $this->info('Signature: '.$payload['signature']);
        $this->info('Transaction Ref: '.$payload['transaction_ref']);
        $this->info('Transaction Type: '.$payload['transaction_type']);
        $this->info('Amount: '.$payload['transaction_amount'].' '.$payload['transaction_currency']);
        $this->info('Operator: '.$payload['transaction_operator']);
        $this->newLine();

        try {
            // Send the webhook request
            $response = Http::acceptJson()
                ->asJson()
                ->post($webhookUrl, $payload);

            if ($response->successful()) {
                $this->info('✅ Transaction successfully processed!');
                $this->newLine();

                $responseData = $response->json();
                if (isset($responseData['message'])) {
                    $this->info('Response: '.$responseData['message']);
                }

                return Command::SUCCESS;
            } else {
                $this->error('❌ Transaction processing failed!');
                $this->error('HTTP Status: '.$response->status());

                $responseData = $response->json();
                if (isset($responseData['message'])) {
                    $this->error('Error: '.$responseData['message']);
                }

                if (isset($responseData['errors'])) {
                    $this->error('Validation errors:');
                    foreach ($responseData['errors'] as $field => $errors) {
                        foreach ($errors as $error) {
                            $this->error("  - {$field}: {$error}");
                        }
                    }
                }

                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('❌ Request failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function buildWebhookPayload(ExternalTransaction $transaction, string $status, string $signature): array
    {
        return [
            'application' => config('services.mycoolpay.public_key'),
            'app_transaction_ref' => $transaction->external_transaction_id,
            'operator_transaction_ref' => sprintf(
                'MP%s.%s.A%d',
                date('Ymd'),
                date('Hi'),
                random_int(10000, 99999)
            ),
            'transaction_ref' => $transaction->gateway_transaction_id ?? '00000000-0000-0000-0000-000000000000',
            'transaction_type' => $this->mapTransactionType($transaction->transaction_type),
            'transaction_amount' => (float) $transaction->amount,
            'transaction_fees' => 2,
            'transaction_currency' => $this->mapCurrency($transaction),
            'transaction_operator' => $this->mapOperator($transaction),
            'transaction_status' => $status,
            'transaction_reason' => $transaction->description ?? 'Transaction processing',
            'transaction_message' => $status === 'SUCCESS'
                ? 'Your transaction has been successfully completed'
                : 'Transaction failed: Insufficient balance',
            'customer_phone_number' => '655332183',
            'signature' => $signature,
        ];
    }

    private function mapTransactionType(ExternalTransactionType $transactionType): string
    {
        return match ($transactionType) {
            ExternalTransactionType::RECHARGE() => MyCoolPayTransactionType::PAYIN()->value,
            ExternalTransactionType::WITHDRAWAL() => MyCoolPayTransactionType::PAYOUT()->value,
            default => MyCoolPayTransactionType::PAYIN()->value,
        };
    }

    private function mapCurrency(ExternalTransaction $transaction): string
    {
        $paymentMethod = $transaction->payment_method;

        if ($paymentMethod === null) {
            return MyCoolPayCurrency::EUR()->value;
        }

        return match ($paymentMethod) {
            PaymentMethod::MOBILE_MONEY(), PaymentMethod::ORANGE_MONEY() => MyCoolPayCurrency::XAF()->value,
            PaymentMethod::BANK_CARD() => MyCoolPayCurrency::EUR()->value,
            default => MyCoolPayCurrency::EUR()->value,
        };
    }

    private function mapOperator(ExternalTransaction $transaction): string
    {
        $paymentMethod = $transaction->payment_method;

        if ($paymentMethod === null) {
            return MyCoolPayOperator::CARD()->value;
        }

        return match ($paymentMethod) {
            PaymentMethod::MOBILE_MONEY() => MyCoolPayOperator::CM_MOMO()->value,
            PaymentMethod::ORANGE_MONEY() => MyCoolPayOperator::CM_OM()->value,
            PaymentMethod::BANK_CARD() => MyCoolPayOperator::CARD()->value,
            default => MyCoolPayOperator::CARD()->value,
        };
    }

    private function isProduction(): bool
    {
        return app()->environment('production');
    }
}
