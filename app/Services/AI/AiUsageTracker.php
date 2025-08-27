<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTOs\AI\AiResponseDTO;
use App\Models\AiUsageLog;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Log;

final class AiUsageTracker
{
    public function logUsage(
        User $user,
        WhatsAppAccount $account,
        ?WhatsAppConversation $conversation,
        ?WhatsAppMessage $message,
        AiResponseDTO $response,
        array $costs,
        int $requestLength,
        ?int $responseTimeMs = null
    ): AiUsageLog {
        $usage = $response->metadata['usage'] ?? [];

        $usageLog = AiUsageLog::create([
            'user_id' => $user->id,
            'whatsapp_account_id' => $account->id,
            'whatsapp_conversation_id' => $conversation?->id,
            'whatsapp_message_id' => $message?->id,
            'ai_model' => $response->metadata['model'] ?? 'unknown',
            'provider' => $response->metadata['provider'] ?? 'unknown',

            'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
            'completion_tokens' => $usage['completion_tokens'] ?? 0,
            'total_tokens' => $usage['total_tokens'] ?? 0,
            'cached_tokens' => $usage['prompt_cache_hit_tokens'] ?? 0,

            'prompt_cost_usd' => $costs['prompt_cost_usd'],
            'completion_cost_usd' => $costs['completion_cost_usd'],
            'cached_cost_usd' => $costs['cached_cost_usd'],
            'total_cost_usd' => $costs['total_cost_usd'],
            'total_cost_xaf' => $costs['total_cost_xaf'],

            'request_length' => $requestLength,
            'response_length' => strlen($response->content),
            'api_attempts' => $response->metadata['attempts'] ?? 1,
            'response_time_ms' => $responseTimeMs,
        ]);

        Log::info('[AI_USAGE_TRACKER] Usage logged successfully', [
            'usage_log_id' => $usageLog->id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'conversation_id' => $conversation?->id,
            'total_cost_usd' => $costs['total_cost_usd'],
            'total_tokens' => $usage['total_tokens'] ?? 0,
        ]);

        return $usageLog;
    }

    public function getUserDailySpend(User $user): float
    {
        return AiUsageLog::byUser($user->id)
            ->byDateRange(now()->startOfDay(), now())
            ->sum('total_cost_usd');
    }

    public function checkUserDailyLimit(User $user, float $dailyLimitUSD = 5.0): bool
    {
        $todaySpend = $this->getUserDailySpend($user);

        if ($todaySpend >= $dailyLimitUSD) {
            Log::warning('[AI_USAGE_TRACKER] User daily limit exceeded', [
                'user_id' => $user->id,
                'today_spend' => $todaySpend,
                'daily_limit' => $dailyLimitUSD,
            ]);

            return false;
        }

        return true;
    }

    public function getTopSpendingUsers(int $limit = 10, string $period = '30_days'): \Illuminate\Database\Eloquent\Collection
    {
        $startDate = match ($period) {
            '24h' => now()->subDay(),
            '7_days' => now()->subWeek(),
            '30_days' => now()->subMonth(),
            '3_months' => now()->subMonths(3),
            default => now()->subMonth(),
        };

        return AiUsageLog::with('user')
            ->byDateRange($startDate, now())
            ->selectRaw('
                user_id,
                SUM(total_cost_usd) as total_cost,
                SUM(total_cost_xaf) as total_cost_xaf,
                COUNT(*) as request_count,
                SUM(total_tokens) as total_tokens
            ')
            ->groupBy('user_id')
            ->orderBy('total_cost', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getGlobalStats(string $period = '30_days'): array
    {
        /*$startDate = match ($period) {
            '24h' => now()->subDay(),
            '7_days' => now()->subWeek(),
            '30_days' => now()->subMonth(),
            '3_months' => now()->subMonths(3),
            default => now()->subMonth(),
        };

        $stats = AiUsageLog::byDateRange($startDate, now())
            ->selectRaw('
                COUNT(*) as total_requests,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT whatsapp_account_id) as unique_accounts,
                COUNT(DISTINCT whatsapp_conversation_id) as unique_conversations,
                SUM(total_tokens) as total_tokens,
                SUM(total_cost_usd) as total_cost_usd,
                SUM(total_cost_xaf) as total_cost_xaf,
                AVG(total_cost_usd) as avg_cost_per_request,
                AVG(response_time_ms) as avg_response_time
            ')
            ->first();

        return [
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'total_requests' => $stats->total_requests ?? 0,
            'unique_users' => $stats->unique_users ?? 0,
            'unique_accounts' => $stats->unique_accounts ?? 0,
            'unique_conversations' => $stats->unique_conversations ?? 0,
            'total_tokens' => $stats->total_tokens ?? 0,
            'total_cost_usd' => round($stats->total_cost_usd ?? 0, 6),
            'total_cost_xaf' => round($stats->total_cost_xaf ?? 0, 2),
            'avg_cost_per_request' => round($stats->avg_cost_per_request ?? 0, 6),
            'avg_response_time' => round($stats->avg_response_time ?? 0, 0),
        ];*/

        return [];
    }
}
