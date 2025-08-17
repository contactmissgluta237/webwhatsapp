<?php

declare(strict_types=1);

namespace App\DTOs\WhatsApp;

use App\DTOs\BaseDTO;
use App\Models\WhatsAppConversation;

final class ConversationContextDTO extends BaseDTO
{
    private const MAX_RECENT_MESSAGES = 25;
    
    public function __construct(
        public int $conversationId,
        public string $chatId,
        public string $contactPhone,
        public bool $isGroup,
        public array $recentMessages = [],
        public ?string $contextualInformation = null,
        public ?array $metadata = []
    ) {}

    public static function fromConversation(WhatsAppConversation $conversation, ?string $contextualInformation = null): self
    {
        $recentMessages = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->limit(self::MAX_RECENT_MESSAGES)
            ->get(['content', 'direction', 'created_at'])
            ->reverse()
            ->values()
            ->toArray();

        return new self(
            conversationId: $conversation->id,
            chatId: $conversation->chat_id,
            contactPhone: $conversation->contact_phone,
            isGroup: $conversation->is_group,
            recentMessages: $recentMessages,
            contextualInformation: $contextualInformation,
            metadata: []
        );
    }

    public function hasRecentMessages(): bool
    {
        return !empty($this->recentMessages);
    }

    public function getFormattedHistory(): string
    {
        if (empty($this->recentMessages)) {
            return '';
        }

        $history = '';
        foreach ($this->recentMessages as $message) {
            // Handle both formats: database format and AI format
            if (isset($message['direction'])) {
                // Database format: direction field
                $role = $message['direction'] === 'incoming' ? 'User' : 'Assistant';
            } elseif (isset($message['role'])) {
                // AI format: role field (from simulation)
                $role = match($message['role']) {
                    'user' => 'User',
                    'assistant' => 'Assistant',
                    'system' => 'System',
                    default => 'User'
                };
            } else {
                // Fallback
                $role = 'User';
            }
            
            $history .= "{$role}: {$message['content']}\n";
        }

        return trim($history);
    }

    public function getContextPrompt(): string
    {
        $prompt = '';

        if ($this->contextualInformation) {
            $prompt .= "Informations contextuelles: {$this->contextualInformation}\n\n";
        }

        if ($this->hasRecentMessages()) {
            $prompt .= "Historique de la conversation:\n{$this->getFormattedHistory()}\n\n";
        }

        return $prompt;
    }
}
