<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Enums\TransactionStatus;
use App\Events\ExternalTransactionWebhookProcessedEvent;
use App\Models\ExternalTransaction;
use App\Models\Geography\Country;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\PaymentIdentifierRequestDTO;
use App\Services\Payment\Exceptions\PaymentGatewayException;
use App\Services\Payment\Gateways\MyCoolPay\DTOs\MyCoolPayPaymentResponseDTO;
use App\Services\Payment\Gateways\MyCoolPay\DTOs\MyCoolPayWebhookDTO;
use App\Services\Payment\Gateways\MyCoolPay\Exceptions\MyCoolPayWebhookException;
use App\Services\Payment\Gateways\MyCoolPay\MyCoolPayMappingHelper;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MyCoolPay\Exception\BadSignatureException;
use MyCoolPay\Exception\KeyMismatchException;
use MyCoolPay\MyCoolPayClient;

final class MyCoolPayGateway implements PaymentGatewayInterface
{
    private string $apiUrl;
    private string $publicKey;
    private string $privateKey;

    public function __construct(
        private readonly MyCoolPayClient $client,
    ) {
        $this->apiUrl = config('services.mycoolpay.api_url');
        $this->publicKey = config('services.mycoolpay.public_key');
        $this->privateKey = config('services.mycoolpay.private_key');

        if (! $this->apiUrl || ! $this->publicKey || ! $this->privateKey) {
            throw new PaymentGatewayException('MyCoolPay configuration is incomplete');
        }
    }

    public function initiatePayment(ExternalTransaction $transaction, PaymentIdentifierRequestDTO $request): MyCoolPayPaymentResponseDTO
    {
        $transaction->load(['wallet.user']);
        $user = $transaction->wallet->user;

        $payload = [
            'transaction_amount' => $transaction->amount,
            'transaction_currency' => $user->currency ?? 'XAF',
            'transaction_reason' => $transaction->description ?? 'Account recharge',
            'app_transaction_ref' => $transaction->external_transaction_id,
            'customer_phone_number' => $request->phoneNumber,
            'customer_name' => "{$user->full_name}",
            'customer_email' => $user->email,
            'customer_lang' => $user->locale ?? 'fr',
        ];

        try {
            Log::info('MyCoolPay payment initiation', [
                'transaction_ref' => $transaction->external_transaction_id,
                'amount' => $transaction->amount,
                'phone' => $request->phoneNumber,
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post("{$this->apiUrl}/{$this->publicKey}/payin", $payload);

            if (! $response->successful()) {
                Log::error('MyCoolPay API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'transaction_ref' => $transaction->external_transaction_id,
                ]);

                throw new PaymentGatewayException(
                    'MyCoolPay API request failed: '.$response->body()
                );
            }

            $responseData = $response->json();

            Log::info('MyCoolPay payment response', [
                'transaction_ref' => $transaction->external_transaction_id,
                'status' => $responseData['status'] ?? 'unknown',
                'action' => $responseData['action'] ?? null,
            ]);

            return MyCoolPayPaymentResponseDTO::fromArray($responseData);

        } catch (ConnectionException $e) {
            Log::error('MyCoolPay connection error', [
                'error' => $e->getMessage(),
                'transaction_ref' => $transaction->external_transaction_id,
            ]);
            throw new PaymentGatewayException('Connection to MyCoolPay failed', 0, $e);
        } catch (RequestException $e) {
            Log::error('MyCoolPay request error', [
                'error' => $e->getMessage(),
                'transaction_ref' => $transaction->external_transaction_id,
            ]);
            throw new PaymentGatewayException('MyCoolPay request failed', 0, $e);
        }
    }

    public function verifyTransaction(string $transactionRef): bool
    {
        // MyCoolPay ne fournit pas d'endpoint de vérification
        // La vérification se fait via le webhook callback uniquement
        Log::info('MyCoolPay verification called - using webhook only', [
            'transaction_ref' => $transactionRef,
        ]);

        return true;
    }

    public function getSupportedCountries(): array
    {
        return ['CM']; // Cameroon
    }

    public function isCountrySupported(Country $country): bool
    {
        return in_array($country->code, $this->getSupportedCountries());
    }

    public function processWebhook(array $webhookData): ExternalTransaction
    {
        Log::info('MyCoolPay Webhook processing started', $webhookData);

        try {
            $this->verifyWebhookSignature($webhookData);

            $webhookDTO = MyCoolPayWebhookDTO::fromArray($webhookData);
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
        $mappedStatus = $this->mapMyCoolPayStatusToTransactionStatus($webhookDTO->transaction_status);
        $mappedPaymentMethod = MyCoolPayMappingHelper::operatorToPaymentMethod($webhookDTO->transaction_operator);

        $transaction->update([
            'status' => $mappedStatus,
            'payment_method' => $mappedPaymentMethod,
            'gateway_transaction_id' => $webhookDTO->transaction_ref,
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
