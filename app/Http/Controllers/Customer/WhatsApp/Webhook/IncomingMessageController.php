<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\WhatsApp\Webhook;

use App\Contracts\WhatsApp\WhatsAppMessageOrchestratorInterface;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Events\WhatsApp\MessageProcessedEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsApp\Webhook\IncomingMessageRequest;
use App\Models\WhatsAppAccount;
use App\Repositories\WhatsAppAccountRepositoryInterface;
use App\Services\WhatsApp\ConversationHistoryService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class IncomingMessageController extends Controller
{
    public function __construct(
        private readonly WhatsAppMessageOrchestratorInterface $orchestrator,
        private readonly ConversationHistoryService $conversationHistory,
        private readonly WhatsAppAccountRepositoryInterface $whatsAppAccountRepository,
    ) {}

    /**
     * Handle incoming WhatsApp message webhook
     * Route: POST /api/whatsapp/webhook/incoming-message
     */
    public function __invoke(IncomingMessageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->logIncomingMessage($validated);

        try {
            $account = $this->whatsAppAccountRepository->findBySessionId($validated['session_id']);
            $messageRequest = WhatsAppMessageRequestDTO::fromWebhookData($validated['message']);
            $conversationHistory = $this->conversationHistory->prepareConversationHistory($account, $messageRequest->from);

            $response = $this->orchestrator->processMessage(
                $account,
                $messageRequest,
                $conversationHistory
            );

            $webhookResponse = $response->toWebhookResponse();

            $this->dispatchEventIfSuccessful($account, $messageRequest, $response);

            return response()->json(
                $webhookResponse,
                $response->wasSuccessful() ? 200 : 500
            );

        } catch (Exception $e) {
            Log::error('[WEBHOOK] Failed to process message', [
                'session_id' => $validated['session_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Failed to process message', 500);
        }
    }

    private function dispatchEventIfSuccessful(
        WhatsAppAccount $account,
        WhatsAppMessageRequestDTO $messageRequest,
        WhatsAppMessageResponseDTO $response,
    ): void {
        if ($response->wasSuccessful()) {
            MessageProcessedEvent::dispatch($account, $messageRequest, $response);

            Log::info('[WEBHOOK] MessageProcessedEvent dispatched', [
                'from_phone' => $messageRequest->from,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function logIncomingMessage(array $validated): void
    {
        Log::info('[WEBHOOK] Incoming WhatsApp message', [
            'session_id' => $validated['session_id'],
            'from' => $validated['message']['from'],
            'message_id' => $validated['message']['id'],
            'body_preview' => substr($validated['message']['body'], 0, 50).'...',
        ]);
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'processed' => false,
            'error' => $message,
        ], $status);
    }
}
