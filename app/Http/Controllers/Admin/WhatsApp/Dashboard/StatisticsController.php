<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WhatsApp\Dashboard;

use App\Enums\WhatsAppStatus;
use App\Http\Controllers\Controller;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\AI\AiUsageTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StatisticsController extends Controller
{
    public function __construct(
        private readonly AiUsageTracker $aiUsageTracker
    ) {}

    /**
     * Get dashboard statistics as JSON.
     *
     * Route: GET /admin/whatsapp/dashboard/statistics
     * Name: admin.whatsapp.dashboard.statistics
     */
    public function __invoke(Request $request): JsonResponse
    {
        $period = $request->get('period', '30_days');

        $data = [
            'global_stats' => $this->aiUsageTracker->getGlobalStats($period),
            'system_stats' => [
                'total_accounts' => WhatsAppAccount::count(),
                'active_accounts' => WhatsAppAccount::where('status', WhatsAppStatus::CONNECTED()->value)->count(),
                'total_conversations' => WhatsAppConversation::count(),
                'total_messages' => WhatsAppMessage::count(),
                'ai_enabled_accounts' => WhatsAppAccount::where('agent_enabled', true)->count(),
            ],
            'top_users' => $this->aiUsageTracker->getTopSpendingUsers(10, $period),
            'period' => $period,
        ];

        return response()->json($data);
    }
}
