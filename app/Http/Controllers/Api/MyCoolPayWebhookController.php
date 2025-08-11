<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MyCoolPayWebhookRequest;
use App\Services\Payment\MyCoolPay\Contracts\MyCoolPayWebhookServiceInterface;
use App\Services\Payment\MyCoolPay\Exceptions\MyCoolPayWebhookException;
use Illuminate\Http\JsonResponse;

final class MyCoolPayWebhookController extends Controller
{
    public function __construct(
        private readonly MyCoolPayWebhookServiceInterface $webhookService
    ) {}

    public function __invoke(MyCoolPayWebhookRequest $request): JsonResponse
    {
        try {
            $this->webhookService->processWebhook($request->validated());

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
