<?php

declare(strict_types=1);

namespace App\Services\Payment\MyCoolPay;

use App\Enums\TransactionStatus;
use App\Events\ExternalTransactionWebhookProcessedEvent;
use App\Models\ExternalTransaction;
use App\Services\Payment\MyCoolPay\Contracts\MyCoolPayWebhookServiceInterface;
use App\Services\Payment\MyCoolPay\DTOs\MyCoolPayWebhookDTO;
use App\Services\Payment\MyCoolPay\Exceptions\MyCoolPayWebhookException;
use Illuminate\Support\Facades\Log;
use MyCoolPay\Exception\BadSignatureException;
use MyCoolPay\Exception\KeyMismatchException;
use MyCoolPay\MyCoolPayClient;

final class MyCoolPayWebhookService implements MyCoolPayWebhookServiceInterface
{
    public function __construct(
        private readonly MyCoolPayClient $client
    ) {}

    public function processWebhook(array $webhookData): ExternalTransaction
    {
        Log::info('MyCoolPay Webhook processing started', $webhookData);

        try {
            $this->verifyWebhookSignature($webhookData);

            $webhookDTO = $this->createWebhookDTO($webhookData);
            $transaction = $this->findTransaction($webhookDTO->app_transaction_ref);

            $this->updateTransaction($transaction, $webhookDTO);

            event(new ExternalTransactionWebhookProcessedEvent($transaction));

            Log::info('MyCoolPay Webhook processed successfully', [
                'transaction_id' => $transaction->id,
                'status' => $transaction->status->value,
            ]);

            return $transaction;

        } catch (KeyMismatchException|BadSignatureException $e) {
            Log::error('MyCoolPay Webhook signature verification failed', [
                'error' => $e->getMessage(),
                'webhook_data' => $webhookData,
            ]);
            throw new MyCoolPayWebhookException('Webhook signature verification failed', 403, $e);
        } catch (\Exception $e) {
            Log::error('MyCoolPay Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'webhook_data' => $webhookData,
            ]);
            throw new MyCoolPayWebhookException('Webhook processing failed', 500, $e);
        }
    }

    private function verifyWebhookSignature(array $webhookData): void
    {
        $this->client->checkCallbackIntegrity($webhookData);
    }

    private function createWebhookDTO(array $webhookData): MyCoolPayWebhookDTO
    {
        return MyCoolPayWebhookDTO::from($webhookData);
    }

    private function findTransaction(string $appTransactionRef): ExternalTransaction
    {
        $transaction = ExternalTransaction::where('external_transaction_id', $appTransactionRef)->first();

        if (! $transaction) {
            Log::warning('MyCoolPay Webhook: Transaction not found', [
                'app_transaction_ref' => $appTransactionRef,
            ]);
            throw new MyCoolPayWebhookException('Transaction not found', 404);
        }

        return $transaction;
    }

    private function updateTransaction(ExternalTransaction $transaction, MyCoolPayWebhookDTO $webhookDTO): void
    {
        $mappedStatus = $this->mapMyCoolPayStatusToTransactionStatus($webhookDTO->status);

        $transaction->update([
            'status' => $mappedStatus,
            'gateway_response' => $webhookDTO->toArray(),
        ]);
    }

    private function mapMyCoolPayStatusToTransactionStatus(string $myCoolPayStatus): TransactionStatus
    {
        return match (strtolower($myCoolPayStatus)) {
            'success' => TransactionStatus::COMPLETED(),
            'failed' => TransactionStatus::FAILED(),
            'cancelled' => TransactionStatus::CANCELLED(),
            'pending' => TransactionStatus::PENDING(),
            default => TransactionStatus::FAILED(),
        };
    }
}
