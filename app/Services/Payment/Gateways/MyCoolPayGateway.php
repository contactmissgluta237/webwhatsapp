<?php

// app/Services/Payment/Gateways/MyCoolPayGateway.php

namespace App\Services\Payment\Gateways;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Services\Payment\DTOs\PaymentInitiateDTO;
use App\Services\Payment\DTOs\PaymentResponseDTO;
use App\Services\Payment\Exceptions\PaymentException;
use Illuminate\Support\Facades\Log;
use MyCoolPay\Http\Exception\HttpException;
use MyCoolPay\Logging\Logger;
use MyCoolPay\MyCoolPayClient;

class MyCoolPayGateway
{
    private MyCoolPayClient $client;

    public function __construct()
    {
        $logger = new Logger('mycoolpay.log', storage_path('logs'));

        $this->client = new MyCoolPayClient(
            config('services.mycoolpay.public_key'),
            config('services.mycoolpay.private_key'),
            $logger,
            config('app.debug')
        );
    }

    public function initiateRecharge(PaymentInitiateDTO $dto): PaymentResponseDTO
    {
        try {
            $response = $this->client->paylink([
                'transaction_amount' => $dto->amount,
                'transaction_currency' => $dto->currency,
                'transaction_reason' => $dto->description ?? 'Payment',
                'app_transaction_ref' => $dto->reference,
                'customer_phone_number' => $dto->customer_phone,
                'customer_name' => $dto->customer_name,
                'customer_email' => $dto->customer_email,
                'customer_lang' => $dto->customer_lang,
            ]);

            Log::info('MyCoolPayGateway: initiateRecharge response', [
                'response_data' => $this->formatResponseData($response),
                'transaction_status' => $response->get('transaction_status'),
            ]);

            return PaymentResponseDTO::from([
                'success' => true,
                'transaction_id' => $response->get('transaction_ref'),
                'status' => $this->mapResponseStatus($response->get('transaction_status')),
                'payment_url' => $response->get('payment_url'),
                'amount' => $dto->amount,
                'gateway_data' => $this->formatResponseData($response),
            ]);

        } catch (HttpException $e) {
            throw new PaymentException('Recharge initiation failed: '.$e->getMessage(), 0, $e);
        }
    }

    public function initiateWithdrawal(PaymentInitiateDTO $dto): PaymentResponseDTO
    {
        try {
            $response = $this->client->payout([
                'transaction_amount' => $dto->amount,
                'transaction_currency' => $dto->currency,
                'transaction_reason' => $dto->description ?? 'Payout',
                'transaction_operator' => $this->mapOperator($dto->payment_method),
                'app_transaction_ref' => $dto->reference,
                'customer_phone_number' => $dto->customer_phone,
                'customer_name' => $dto->customer_name,
                'customer_email' => $dto->customer_email,
                'customer_lang' => $dto->customer_lang,
            ]);

            Log::info('MyCoolPayGateway: initiateWithdrawal response', [
                'response_data' => $this->formatResponseData($response),
                'transaction_status' => $response->get('transaction_status'),
            ]);

            return PaymentResponseDTO::from([
                'success' => $response->getStatusCode() === 200,
                'transaction_id' => $response->get('transaction_ref'),
                'status' => $this->mapResponseStatus($response->get('transaction_status')),
                'payment_url' => null, // No redirect for payout
                'amount' => $dto->amount,
                'gateway_data' => $this->formatResponseData($response),
            ]);

        } catch (HttpException $e) {
            throw new PaymentException('Withdrawal initiation failed: '.$e->getMessage(), 0, $e);
        }
    }

    public function checkStatus(string $transactionId): PaymentResponseDTO
    {
        try {
            $response = $this->client->checkStatus($transactionId);

            Log::info('MyCoolPayGateway: checkStatus response', [
                'response_data' => $this->formatResponseData($response),
                'transaction_status' => $response->get('transaction_status'),
            ]);

            return PaymentResponseDTO::from([
                'success' => true,
                'transaction_id' => $transactionId,
                'status' => $this->mapResponseStatus($response->get('transaction_status')),
                'payment_url' => $response->get('payment_url'),
                'amount' => $response->get('transaction_amount'),
                'gateway_data' => $this->formatResponseData($response),
            ]);

        } catch (HttpException $e) {
            throw new PaymentException('Status check failed: '.$e->getMessage(), 0, $e);
        }
    }

    public function getBalance(): float
    {
        try {
            $response = $this->client->getBalance();

            return (float) $response->get('balance');

        } catch (HttpException $e) {
            throw new PaymentException('Balance check failed: '.$e->getMessage(), 0, $e);
        }
    }

    private function mapPaymentMethod(PaymentMethod $paymentMethod): string
    {
        return match ($paymentMethod) {
            PaymentMethod::MOBILE_MONEY() => 'mtn_mobile_money',
            PaymentMethod::ORANGE_MONEY() => 'orange_money',
            PaymentMethod::BANK_CARD() => 'card',
            default => throw new \InvalidArgumentException("Unsupported payment method: {$paymentMethod->value}"),
        };
    }

    private function mapResponseStatus(?string $gatewayStatus): PaymentStatus
    {
        if ($gatewayStatus === null) {
            Log::warning('MyCoolPayGateway: gatewayStatus is null for initiated transaction, defaulting to PENDING', ['gatewayStatus' => $gatewayStatus]);

            return PaymentStatus::PENDING();
        }

        return match (strtolower($gatewayStatus)) {
            'pending', 'initiated' => PaymentStatus::PENDING(),
            'success', 'completed', 'paid' => PaymentStatus::COMPLETED(),
            'failed', 'error' => PaymentStatus::FAILED(),
            'cancelled', 'canceled' => PaymentStatus::CANCELLED(),
            default => PaymentStatus::PENDING(),
        };
    }

    private function mapOperator(PaymentMethod $paymentMethod): string
    {
        return match ($paymentMethod->value) {
            'mobile_money' => 'CM_MOMO',
            'orange_money' => 'CM_OM',
            default => throw new PaymentException('Unsupported payment method for payout: '.$paymentMethod->value),
        };
    }

    private function formatResponseData($response): array
    {
        return [
            'transaction_ref' => $response->get('transaction_ref'),
            'transaction_status' => $response->get('transaction_status'),
            'transaction_amount' => $response->get('transaction_amount'),
            'transaction_currency' => $response->get('transaction_currency'),
            'payment_url' => $response->get('payment_url'),
            'created_at' => $response->get('created_at'),
        ];
    }
}
