<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WhatsApp\Conversation;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppConversation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

final class ShowController extends Controller
{
    /**
     * Display the specified conversation with messages and AI usage.
     *
     * Route: GET /admin/whatsapp/conversations/{conversation}
     * Name: admin.whatsapp.conversations.show
     */
    public function __invoke(WhatsAppConversation $conversation): View
    {
        $conversation->load([
            'whatsappAccount:id,session_name,user_id',
            'whatsappAccount.user:id,name,email',
            'messages' => function (Builder $query): void {
                $query->orderBy('created_at', 'asc');
            },
        ]);

        // Get AI usage logs with detailed information
        $aiUsage = $conversation->aiUsageLogs()
            ->with('message:id,content,created_at,direction')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.whatsapp.conversations.show', [
            'conversation' => $conversation,
            'stats' => [],
            'aiUsage' => $aiUsage,
            'pageTitle' => "Conversation - {$conversation->getDisplayName()}",
        ]);
    }
}
