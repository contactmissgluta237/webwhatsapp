<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WhatsApp\Dashboard;

use App\Enums\WhatsAppStatus;
use App\Http\Controllers\Controller;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\AI\AiUsageTracker;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class IndexController extends Controller
{
    public function __construct(
        private readonly AiUsageTracker $aiUsageTracker
    ) {}

    /**
     * Display the WhatsApp admin dashboard.
     *
     * Route: GET /admin/whatsapp/dashboard
     * Name: admin.whatsapp.dashboard
     */
    public function __invoke(Request $request): View
    {
        $period = $request->get('period', '30_days');

        // Global statistics
        $globalStats = $this->aiUsageTracker->getGlobalStats($period);

        // System-wide counts
        $systemStats = [
            'total_accounts' => WhatsAppAccount::count(),
            'active_accounts' => WhatsAppAccount::where('status', WhatsAppStatus::CONNECTED()->value)->count(),
            'total_conversations' => WhatsAppConversation::count(),
            'total_messages' => WhatsAppMessage::count(),
            'ai_enabled_accounts' => WhatsAppAccount::where('agent_enabled', true)->count(),
        ];

        // Top spending users
        $topUsers = $this->aiUsageTracker->getTopSpendingUsers(10, $period);

        // Recent AI activity
        $recentActivity = \App\Models\AiUsageLog::with([
            'user:id,name',
            'whatsappAccount:id,session_name',
            'conversation:id,contact_name,contact_phone',
        ])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Daily usage trend (last 30 days)
        $usageTrend = \App\Models\AiUsageLog::selectRaw('DATE(created_at) as date, COUNT(*) as requests, SUM(total_cost_xaf) as cost')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        return view('admin.whatsapp.dashboard', [
            'globalStats' => $globalStats,
            'systemStats' => $systemStats,
            'topUsers' => $topUsers,
            'recentActivity' => $recentActivity,
            'usageTrend' => $usageTrend,
            'period' => $period,
            'pageTitle' => 'Tableau de Bord WhatsApp',
        ]);
    }
}
