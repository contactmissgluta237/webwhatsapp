<?php

declare(strict_types=1);

namespace App\Listeners\WhatsApp;

use App\DTOs\AI\AiResponseDTO;
use App\Events\WhatsApp\AiResponseGenerated;
use App\Services\AI\AiUsageTracker;
use Exception;
use Illuminate\Support\Facades\Log;

class TrackAiUsageListener
{
    public function __construct(
        private readonly AiUsageTracker $aiUsageTracker
    ) {}

    public function handle(AiResponseGenerated $event): void
    {
        try {
            $conversation = $this->findConversation($event);
            $message = $this->findMessage($event, $conversation);
            $costs = $this->extractCosts($event);

            if (empty($costs)) {
                Log::warning('[AI_USAGE_TRACKER] No cost data available for tracking', [
                    'account_id' => $event->account->id,
                    'from' => $event->messageRequest->from,
                ]);

                return;
            }

            $aiResponseDTO = $this->convertToAiResponseDTO($event);

            $this->aiUsageTracker->logUsage(
                user: $event->account->user,
                account: $event->account,
                conversation: $conversation,
                message: $message,
                response: $aiResponseDTO,
                costs: $costs,
                requestLength: strlen($event->requestBody),
                responseTimeMs: (int) round($event->processingTimeMs)
            );

            Log::info('[AI_USAGE_TRACKER] AI usage tracked successfully', [
                'account_id' => $event->account->id,
                'conversation_id' => $conversation?->id,
                'message_id' => $message?->id,
                'cost_usd' => $costs['total_cost_usd'] ?? 0,
                'cost_xaf' => $costs['total_cost_xaf'] ?? 0,
                'tokens' => $event->aiResponse->tokensUsed ?? 0,
                'processing_time_ms' => (int) round($event->processingTimeMs),
            ]);

        } catch (Exception $e) {
            Log::error('[AI_USAGE_TRACKER] Failed to track AI usage', [
                'account_id' => $event->account->id,
                'from' => $event->messageRequest->from,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function findConversation(AiResponseGenerated $event): ?\App\Models\WhatsAppConversation
    {
        /** @var \App\Models\WhatsAppConversation|null */
        return $event->account
            ->conversations()
            ->where('chat_id', $event->messageRequest->from)
            ->first();
    }

    private function findMessage(AiResponseGenerated $event, ?\App\Models\WhatsAppConversation $conversation): ?\App\Models\WhatsAppMessage
    {
        if (! $conversation) {
            return null;
        }

        return $conversation
            ->messages()
            ->where('whatsapp_message_id', $event->messageRequest->id)
            ->first();
    }

    private function extractCosts(AiResponseGenerated $event): array
    {
        $metadata = $event->aiResponse->metadata ?? [];

        return $metadata['costs'] ?? [];
    }

    private function convertToAiResponseDTO(AiResponseGenerated $event): AiResponseDTO
    {
        $metadata = $event->aiResponse->metadata ?? [];

        return new AiResponseDTO(
            content: $event->aiResponse->response,
            tokensUsed: $event->aiResponse->tokensUsed ?? 0,
            metadata: array_merge($metadata, [
                'model' => $event->aiResponse->model,
                'confidence' => $event->aiResponse->confidence ?? 1.0,
            ])
        );
    }
}
