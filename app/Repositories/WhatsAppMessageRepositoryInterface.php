<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\WhatsApp\MessageExchangeResult;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Collection;

interface WhatsAppMessageRepositoryInterface
{
    /**
     * Find or create a conversation for the given account and message.
     */
    public function findOrCreateConversation(
        WhatsAppAccount $account,
        WhatsAppMessageRequestDTO $messageRequest
    ): WhatsAppConversation;

    /**
     * Store an incoming message from user.
     */
    public function storeIncomingMessage(
        WhatsAppConversation $conversation,
        WhatsAppMessageRequestDTO $messageRequest
    ): WhatsAppMessage;

    /**
     * Store an outgoing AI-generated message.
     */
    public function storeOutgoingMessage(
        WhatsAppConversation $conversation,
        WhatsAppAccount $account,
        WhatsAppMessageResponseDTO $aiResponse
    ): ?WhatsAppMessage;

    /**
     * Store both incoming and outgoing messages in a single transaction.
     */
    public function storeMessageExchange(
        WhatsAppAccount $account,
        WhatsAppMessageRequestDTO $incomingMessage,
        WhatsAppMessageResponseDTO $aiResponse
    ): MessageExchangeResult;

    /**
     * Get recent messages for conversation history.
     *
     * @return Collection<int, WhatsAppMessage>
     */
    public function getRecentMessages(
        WhatsAppConversation $conversation,
        int $limit = 20
    ): Collection;
}
