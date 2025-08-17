<?php

declare(strict_types=1);

namespace App\Http\Controllers\WhatsApp\Webhook;

use App\Contracts\WhatsApp\WhatsAppMessageOrchestratorInterface;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsApp\Webhook\IncomingMessageRequest;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class IncomingMessageController extends Controller
{
    public function __construct(
        private readonly WhatsAppMessageOrchestratorInterface $orchestrator
    ) {}

    /**
     * Handle incoming WhatsApp message from Node.js bridge
     *
     * Route: POST /api/whatsapp/webhook/incoming-message
     */
    public function __invoke(IncomingMessageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        Log::info('[WEBHOOK CONTROLLER] Incoming WhatsApp message from Node.js bridge', [
            'session_id' => $validated['session_id'],
            'from' => $validated['message']['from'],
            'message_id' => $validated['message']['id'],
            'body_preview' => substr($validated['message']['body'], 0, 50).'...',
        ]);

        try {
            // Step 1: Create account metadata from session information
            $accountMetadata = $this->orchestrator->createAccountMetadata(
                $validated['session_id'],
                $validated['session_name']
            );

            // Step 2: Create message request DTO from webhook data
            $messageRequest = WhatsAppMessageRequestDTO::fromWebhookData($validated['message']);

            // Step 3: Process through orchestrator
            $response = $this->orchestrator->processIncomingMessage(
                $accountMetadata,
                $messageRequest
            );

            // Step 4: Return formatted response
            $responseData = $response->toWebhookResponse();

            Log::info('[WEBHOOK CONTROLLER] Message processing completed', [
                'session_id' => $validated['session_id'],
                'success' => $responseData['success'],
                'has_response' => $response->hasAiResponse,
            ]);

            return response()->json($responseData, $responseData['success'] ? 200 : 500);

        } catch (Exception $e) {
            Log::error('[WEBHOOK CONTROLLER] Failed to process incoming WhatsApp message', [
                'session_id' => $validated['session_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'processed' => false,
                'error' => 'Failed to process message',
            ], 500);
        }
    }
}
