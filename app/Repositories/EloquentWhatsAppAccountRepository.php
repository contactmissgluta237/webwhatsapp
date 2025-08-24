<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\WhatsAppAccount;
use Exception;
use Illuminate\Support\Facades\Log;

final class EloquentWhatsAppAccountRepository implements WhatsAppAccountRepositoryInterface
{
    public function findBySessionId(string $sessionId): ?WhatsAppAccount
    {
        $account = WhatsAppAccount::where('session_id', $sessionId)->first();

        if (! $account) {
            Log::warning('[REPOSITORY] WhatsApp account not found', [
                'session_id' => $sessionId,
            ]);
            throw new Exception("WhatsApp account not found for session: {$sessionId}");
        }

        if (! $account->ai_model_id) {
            Log::warning('[REPOSITORY] WhatsApp account not configured for AI (no AI model)', [
                'session_id' => $sessionId,
                'account_id' => $account->id,
            ]);

            throw new Exception("WhatsApp account is disabled or not configured for AI: {$sessionId}");
        }

        if (! $account->agent_enabled) {
            Log::warning('[REPOSITORY] WhatsApp account is inactive', [
                'session_id' => $sessionId,
                'account_id' => $account->id,
            ]);

            throw new Exception("WhatsApp account is inactive: {$sessionId}");
        }

        return $account;
    }
}
