<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payment\Gateways\MyCoolPay\Exceptions\MyCoolPayWebhookException;
use App\Services\Payment\Gateways\MyCoolPay\MyCoolPayWebhookRequest;
use App\Services\Payment\Gateways\MyCoolPayGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class MyCoolPayWebhookController extends Controller
{
    public function __construct(
        private readonly MyCoolPayGateway $gateway,
    ) {}

    public function __invoke(MyCoolPayWebhookRequest $request): JsonResponse
    {
        Log::info('Received MyCoolPay webhook', $request->validated());

        try {
            $this->gateway->processWebhook($request->validated());

            return response()->json([
                'message' => 'Webhook received and processed',
            ], 200);

        } catch (MyCoolPayWebhookException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }
}
