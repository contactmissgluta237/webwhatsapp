<?php

declare(strict_types=1);

namespace App\Http\Controllers\WhatsApp\Webhook;

use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsApp\Webhook\IncomingMessageRequest;
use App\Services\WhatsApp\AI\WhatsAppAIProcessorServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class IncomingMessageController extends Controller
{
    public function __construct(
        private readonly WhatsAppAIProcessorServiceInterface $aiProcessor
    ) {}

    /**
     * Handle incoming WhatsApp message from Node.js bridge
     *
     * Route: POST /api/whatsapp/webhook/incoming-message
     */
    public function __invoke(IncomingMessageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        Log::info('[WEBHOOK RECEIVED] Incoming WhatsApp message from Node.js bridge', [
            'session_id' => $validated['session_id'],
            'from' => $validated['message']['from'],
            'message_id' => $validated['message']['id'],
            'body_preview' => substr($validated['message']['body'], 0, 50) . '...', // Ajout d'un aperçu du corps du message
        ]);

        try {
            $response = $this->aiProcessor->processIncomingMessage(
                $validated['session_id'],
                $validated['session_name'],
                $validated['message']
            );

            if ($response['has_ai_response']) {
                return response()->json([
                    'success' => true,
                    'response_message' => $response['ai_response'],
                    'processed' => true,
                ]);
            }

            return response()->json([
                'success' => true,
                'processed' => true,
                'message' => 'Message stored successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process incoming WhatsApp message', [
                'session_id' => $validated['session_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process message',
            ], 500);
        }
    }
}
