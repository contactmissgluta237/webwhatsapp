<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WhatsApp\Account;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

final class ShowController extends Controller
{
    /**
     * Display the specified WhatsApp account.
     *
     * Route: GET /admin/whatsapp/accounts/{account}
     * Name: admin.whatsapp.accounts.show
     */
    public function __invoke(WhatsAppAccount $account): View
    {
        $account->load([
            'user:id,name,email',
            'conversations' => function (Builder $query): void {
                $query->withCount('messages')
                    ->orderBy('last_message_at', 'desc')
                    ->limit(10);
            },
        ]);

        // Get AI usage statistics
        $recentUsage = $account->aiUsageLogs()
            ->with('conversation:id,contact_name,contact_phone')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.whatsapp.accounts.show', [
            'account' => $account,
            'stats' => [],
            'recentUsage' => $recentUsage,
            'pageTitle' => "Compte WhatsApp - {$account->session_name}",
        ]);
    }
}
