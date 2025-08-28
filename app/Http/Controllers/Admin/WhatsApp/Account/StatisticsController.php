<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WhatsApp\Account;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\WhatsAppAccount;
use Illuminate\Http\JsonResponse;

final class StatisticsController extends Controller
{
    /**
     * Get statistics for a specific WhatsApp account.
     *
     * Route: GET /admin/whatsapp/accounts/{account}/statistics
     * Name: admin.whatsapp.accounts.statistics
     */
    public function __invoke(WhatsAppAccount $account): JsonResponse
    {
        // Get AI usage statistics for this account (last 30 days)
        $startDate = now()->subMonth();

        $stats = AiUsageLog::where('whatsapp_account_id', $account->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total_requests,
                COUNT(DISTINCT whatsapp_conversation_id) as unique_conversations,
                SUM(total_tokens) as total_tokens,
                SUM(total_cost_usd) as total_cost_usd,
                SUM(total_cost_xaf) as total_cost_xaf,
                AVG(total_cost_usd) as avg_cost_per_request,
                AVG(response_time_ms) as avg_response_time
            ')
            ->first();

        return response()->json([
            'account_id' => $account->id,
            'session_name' => $account->session_name,
            'period' => '30_days',
            'start_date' => $startDate->toDateString(),
            'stats' => [
                'total_requests' => $stats->total_requests ?? 0,
                'unique_conversations' => $stats->unique_conversations ?? 0,
                'total_tokens' => $stats->total_tokens ?? 0,
                'total_cost_usd' => round($stats->total_cost_usd ?? 0, 6),
                'total_cost_xaf' => round($stats->total_cost_xaf ?? 0, 2),
                'avg_cost_per_request' => round($stats->avg_cost_per_request ?? 0, 6),
                'avg_response_time' => round($stats->avg_response_time ?? 0, 0),
            ],
        ]);
    }
}
