<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\WhatsAppAccount;

interface WhatsAppAccountRepositoryInterface
{
    /**
     * Find WhatsApp account by session ID.
     *
     * @param string $sessionId
     * @return WhatsAppAccount|null
     * @throws \Exception If account is not found or not configured for AI.
     */
    public function findBySessionId(string $sessionId): ?WhatsAppAccount;
}
