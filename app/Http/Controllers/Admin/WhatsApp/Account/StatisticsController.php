<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WhatsApp\Account;

use App\DTOs\WhatsApp\WhatsAppAccountStatsDTO;
use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\WhatsAppAccount;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

final class StatisticsController extends Controller
{
    private const STATISTICS_PERIOD_DAYS = 30;
    private const PERIOD_IDENTIFIER = '30_days';

    /**
     * Get statistics for a specific WhatsApp account.
     *
     * Route: GET /admin/whatsapp/accounts/{account}/statistics
     * Name: admin.whatsapp.accounts.statistics
     */
    public function __invoke(WhatsAppAccount $account): JsonResponse
    {
        $startDate = $this->calculateStartDate();
        $rawStats = $this->fetchAiUsageStats($account, $startDate);
        $statsArray = $this->transformRawStatsToArray($rawStats);
        $stats = WhatsAppAccountStatsDTO::from($statsArray);

        return $this->buildJsonResponse($account, $startDate, $stats);
    }

    private function calculateStartDate(): Carbon
    {
        return now()->subDays(self::STATISTICS_PERIOD_DAYS);
    }

    private function fetchAiUsageStats(WhatsAppAccount $account, Carbon $startDate): ?Model
    {
        return AiUsageLog::where('whatsapp_account_id', $account->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw($this->buildStatsSelectRaw())
            ->first();
    }

    private function buildStatsSelectRaw(): string
    {
        return '
            COUNT(*) as total_requests,
            COUNT(DISTINCT whatsapp_conversation_id) as unique_conversations,
            SUM(total_tokens) as total_tokens,
            SUM(total_cost_usd) as total_cost_usd,
            SUM(total_cost_xaf) as total_cost_xaf,
            AVG(total_cost_usd) as avg_cost_per_request,
            AVG(response_time_ms) as avg_response_time
        ';
    }

    private function transformRawStatsToArray(?Model $rawStats): array
    {
        if (! $rawStats) {
            return $this->getDefaultStatsArray();
        }

        $rawArray = $rawStats->toArray();

        return [
            'total_requests' => (int) ($rawArray['total_requests'] ?? 0),
            'unique_conversations' => (int) ($rawArray['unique_conversations'] ?? 0),
            'total_tokens' => (int) ($rawArray['total_tokens'] ?? 0),
            'total_cost_usd' => (float) ($rawArray['total_cost_usd'] ?? 0.0),
            'total_cost_xaf' => (float) ($rawArray['total_cost_xaf'] ?? 0.0),
            'avg_cost_per_request' => (float) ($rawArray['avg_cost_per_request'] ?? 0.0),
            'avg_response_time' => (float) ($rawArray['avg_response_time'] ?? 0.0),
        ];
    }

    private function getDefaultStatsArray(): array
    {
        return [
            'total_requests' => 0,
            'unique_conversations' => 0,
            'total_tokens' => 0,
            'total_cost_usd' => 0.0,
            'total_cost_xaf' => 0.0,
            'avg_cost_per_request' => 0.0,
            'avg_response_time' => 0.0,
        ];
    }

    private function buildJsonResponse(WhatsAppAccount $account, Carbon $startDate, WhatsAppAccountStatsDTO $stats): JsonResponse
    {
        return response()->json([
            'account_id' => $account->id,
            'session_name' => $account->session_name,
            'period' => self::PERIOD_IDENTIFIER,
            'start_date' => $startDate->toDateString(),
            'stats' => $stats->toArrayWithRounding(),
        ]);
    }
}
