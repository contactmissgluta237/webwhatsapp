<?php

namespace App\Http\Controllers\Test;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Services\Payment\DTOs\PaymentInitiateDTO;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class PaymentTestController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function test(string $action, int $amount): JsonResponse|RedirectResponse
    {
        try {
            // Validate action
            if (! in_array($action, ['recharge', 'withdraw'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Action must be either "recharge" or "withdraw"',
                ], 400);
            }

            // Validate amount
            if ($amount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Amount must be positive',
                ], 400);
            }

            // Create test DTO
            $dto = PaymentInitiateDTO::from([
                'amount' => $amount,
                'payment_method' => PaymentMethod::MOBILE_MONEY(),
                'reference' => 'TEST_'.strtoupper($action).'_'.uniqid(),
                'customer_phone' => '699123456',
                'customer_name' => 'Test Customer',
                'customer_email' => 'test@example.com',
                'description' => "Test {$action} - {$amount} FCFA",
            ]);

            // Execute payment (synchrone)
            if ($action === 'recharge') {
                $result = $this->paymentService->initiateRecharge($dto);
            } else {
                $result = $this->paymentService->initiateWithdrawal($dto);
            }

            // If a payment URL is provided, redirect the user
            if ($result->payment_url) {
                return redirect($result->payment_url);
            }

            return response()->json([
                'success' => true,
                'action' => $action,
                'amount' => $amount,
                'reference' => $dto->reference,
                'result' => [
                    'success' => $result->success,
                    'transaction_id' => $result->transaction_id,
                    'status' => $result->status->value,
                    'payment_url' => $result->payment_url,
                    'message' => $result->message,
                    'raw_response' => $result->gateway_data,
                ],
            ]);

        } catch (PaymentException $e) {
            return response()->json([
                'success' => false,
                'action' => $action,
                'amount' => $amount,
                'error' => 'Payment Error: '.$e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Payment test unexpected error', [
                'action' => $action,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'action' => $action,
                'amount' => $amount,
                'error' => 'System Error: '.$e->getMessage(),
            ], 500);
        }
    }

    public function checkBalance(): JsonResponse
    {
        try {
            $balance = $this->paymentService->getBalance(PaymentMethod::MOBILE_MONEY());

            return response()->json([
                'success' => true,
                'balance' => $balance,
                'currency' => 'XAF',
            ]);

        } catch (PaymentException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Balance Check Error: '.$e->getMessage(),
            ], 422);
        }
    }

    public function checkStatus(string $transactionId): JsonResponse
    {
        try {
            $result = $this->paymentService->checkStatus(
                PaymentMethod::MOBILE_MONEY(),
                $transactionId
            );

            return response()->json([
                'success' => true,
                'transaction_id' => $transactionId,
                'status' => $result->status->value,
                'message' => $result->message,
                'data' => $result->gateway_data,
            ]);

        } catch (PaymentException $e) {
            return response()->json([
                'success' => false,
                'transaction_id' => $transactionId,
                'error' => 'Status Check Error: '.$e->getMessage(),
            ], 422);
        }
    }
}
