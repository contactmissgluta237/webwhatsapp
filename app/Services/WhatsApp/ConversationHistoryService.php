<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final class ConversationHistoryService
{
    private const DEFAULT_MESSAGE_LIMIT = 20;
    private const MAX_MESSAGE_LIMIT = 50;
    private const CONTEXT_WINDOW_HOURS = 24;

    /**
     * Prepare conversation history in structured format for orchestrator
     */
    public function prepareConversationHistory(
        WhatsAppAccount $account,
        string $fromPhone,
        ?int $messageLimit = null
    ): string {
        $limit = $this->validateMessageLimit($messageLimit);

        Log::info('[CONVERSATION_HISTORY] Loading conversation history', [
            'session_id' => $account->session_id,
            'from_phone' => $fromPhone,
            'message_limit' => $limit,
        ]);

        $conversation = $this->findConversation($account, $fromPhone);
        if (! $conversation) {
            Log::info('[CONVERSATION_HISTORY] No conversation found', [
                'session_id' => $account->session_id,
                'from_phone' => $fromPhone,
            ]);

            return '';
        }

        $messages = $this->loadRecentMessages($conversation, $limit);
        if ($messages->isEmpty()) {
            Log::info('[CONVERSATION_HISTORY] No messages found in conversation', [
                'session_id' => $account->session_id,
                'conversation_id' => $conversation->id,
            ]);

            return '';
        }

        $formattedHistory = $this->formatConversationHistory($messages);

        Log::info('[CONVERSATION_HISTORY] History prepared successfully', [
            'session_id' => $account->session_id,
            'conversation_id' => $conversation->id,
            'messages_count' => $messages->count(),
            'context_length' => strlen($formattedHistory),
            'oldest_message' => $messages->first()?->created_at?->format('Y-m-d H:i:s'),
            'newest_message' => $messages->last()?->created_at?->format('Y-m-d H:i:s'),
        ]);

        return $formattedHistory;
    }

    /**
     * Get a quick summary of the recent conversation
     */
    public function getConversationSummary(WhatsAppAccount $account, string $fromPhone): array
    {
        $conversation = $this->findConversation($account, $fromPhone);
        if (! $conversation) {
            return [
                'exists' => false,
                'total_messages' => 0,
                'recent_messages' => 0,
                'last_activity' => null,
                'ai_ratio' => 0.0,
            ];
        }

        $recentMessages = $this->loadRecentMessages($conversation, 10);
        $aiMessages = $recentMessages->where('is_ai_generated', true)->count();

        return [
            'exists' => true,
            'total_messages' => $conversation->messages()->count(),
            'recent_messages' => $recentMessages->count(),
            'last_activity' => $conversation->last_message_at,
            'ai_ratio' => $recentMessages->count() > 0 ? $aiMessages / $recentMessages->count() : 0.0,
        ];
    }

    private function validateMessageLimit(?int $messageLimit): int
    {
        if ($messageLimit === null) {
            return self::DEFAULT_MESSAGE_LIMIT;
        }

        return min(max($messageLimit, 1), self::MAX_MESSAGE_LIMIT);
    }

    private function findConversation(WhatsAppAccount $account, string $fromPhone): ?WhatsAppConversation
    {
        return WhatsAppConversation::where('whatsapp_account_id', $account->id)
            ->where('contact_phone', $fromPhone)
            ->first();
    }

    /**
     * Load recent messages with contextual time window
     */
    private function loadRecentMessages(WhatsAppConversation $conversation, int $limit): Collection
    {
        $contextWindow = Carbon::now()->subHours(self::CONTEXT_WINDOW_HOURS);

        return $conversation->messages()
            ->where('created_at', '>=', $contextWindow)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Format history with better contextualization
     */
    private function formatConversationHistory(Collection $messages): string
    {
        if ($messages->isEmpty()) {
            return '';
        }

        $formattedMessages = [];
        $currentDate = null;

        /** @var WhatsAppMessage $message */
        foreach ($messages as $message) {
            $messageDate = $message->created_at->format('Y-m-d');

            // Add date separator if necessary
            if ($currentDate !== $messageDate) {
                if ($currentDate !== null) {
                    $formattedMessages[] = '--- '.$message->created_at->format('d/m/Y').' ---';
                }
                $currentDate = $messageDate;
            }

            $formattedMessages[] = $this->formatSingleMessage($message);
        }

        return implode("\n", $formattedMessages);
    }

    /**
     * Format individual message with more context
     */
    private function formatSingleMessage(WhatsAppMessage $message): string
    {
        $timestamp = $message->created_at->format('H:i');
        $content = trim($message->content);

        if ($message->isInbound()) {
            return "[$timestamp] Client: $content";
        }

        // For outbound messages, distinguish AI vs manual
        if ($message->is_ai_generated) {
            $confidence = $message->ai_confidence ?
                ' (conf: '.number_format($message->ai_confidence * 100, 0).'%)' : '';

            return "[$timestamp] Assistant$confidence: $content";
        }

        return "[$timestamp] Agent: $content";
    }
}
