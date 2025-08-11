<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\AI;

interface WhatsAppAIProcessorServiceInterface
{
    public function processIncomingMessage(string $sessionId, string $sessionName, array $messageData): array;
}
