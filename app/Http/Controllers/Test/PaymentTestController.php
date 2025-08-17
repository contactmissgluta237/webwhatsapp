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
    private const VALID_ACTIONS = ['recharge', 'withdraw'];
    private const TEST_CUSTOMER = [
        'phone' => '699123456',
        'name' => 'Test Customer',
        'email' => 'test@example.com',
    ];

    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    public function test(string $action, int $amount): JsonResponse|RedirectResponse
    {
        try {
            // Validate inputs
            $validationError = $this->validateTestInputs($action, $amount);
            if ($validationError) {
                return $validationError;
            }

            // Create and execute payment
            $dto = $this->createTestPaymentDTO($action, $amount);
            $result = $this->executePaymentAction($action, $dto);

            // Handle redirect if payment URL exists
            if ($result->payment_url) {
                return redirect($result->payment_url);
            }

            return $this->buildSuccessResponse($action, $amount, $dto, $result);

        } catch (PaymentException $e) {
            return $this->buildErrorResponse($action, $amount, 'Payment Error: '.$e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->handleUnexpectedError($action, $amount, $e);
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

    /**
     * Validate test inputs
     */
    private function validateTestInputs(string $action, int $amount): ?JsonResponse
    {
        if (! in_array($action, self::VALID_ACTIONS)) {
            return response()->json([
                'success' => false,
                'message' => 'Action must be either "recharge" or "withdraw"',
            ], 400);
        }

        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Amount must be positive',
            ], 400);
        }

        return null;
    }

    /**
     * Create test payment DTO
     */
    private function createTestPaymentDTO(string $action, int $amount): PaymentInitiateDTO
    {
        return PaymentInitiateDTO::from([
            'amount' => $amount,
            'payment_method' => PaymentMethod::MOBILE_MONEY(),
            'reference' => $this->generateTestReference($action),
            'customer_phone' => self::TEST_CUSTOMER['phone'],
            'customer_name' => self::TEST_CUSTOMER['name'],
            'customer_email' => self::TEST_CUSTOMER['email'],
            'description' => "Test {$action} - {$amount} FCFA",
        ]);
    }

    /**
     * Execute payment action based on type
     */
    private function executePaymentAction(string $action, PaymentInitiateDTO $dto): mixed
    {
        return match ($action) {
            'recharge' => $this->paymentService->initiateRecharge($dto),
            'withdraw' => $this->paymentService->initiateWithdrawal($dto),
            default => throw new \InvalidArgumentException("Invalid action: {$action}")
        };
    }

    /**
     * Generate test reference
     */
    private function generateTestReference(string $action): string
    {
        return 'TEST_'.strtoupper($action).'_'.uniqid();
    }

    /**
     * Build success response
     */
    private function buildSuccessResponse(
        string $action,
        int $amount,
        PaymentInitiateDTO $dto,
        mixed $result
    ): JsonResponse {
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
    }

    /**
     * Build error response
     */
    private function buildErrorResponse(
        string $action,
        int $amount,
        string $errorMessage,
        int $statusCode
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'action' => $action,
            'amount' => $amount,
            'error' => $errorMessage,
        ], $statusCode);
    }

    /**
     * Handle unexpected errors with logging
     */
    private function handleUnexpectedError(string $action, int $amount, \Exception $e): JsonResponse
    {
        Log::error('Payment test unexpected error', [
            'action' => $action,
            'amount' => $amount,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return $this->buildErrorResponse(
            $action,
            $amount,
            'System Error: '.$e->getMessage(),
            500
        );
    }
}
