<?php

declare(strict_types=1);

namespace App\Repositories\WhatsApp\Contracts;

use App\Models\WhatsAppAccount;

interface WhatsAppAccountRepositoryInterface
{
    /**
     * Find WhatsApp account by session ID.
     *
     * @throws \Exception If account is not found or not configured for AI.
     */
    public function findBySessionId(string $sessionId): ?WhatsAppAccount;
}
