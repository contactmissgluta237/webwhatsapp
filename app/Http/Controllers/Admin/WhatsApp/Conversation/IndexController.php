<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WhatsApp\Conversation;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class IndexController extends Controller
{
    /**
     * Display a listing of conversations.
     *
     * Route: GET /admin/whatsapp/conversations
     * Name: admin.whatsapp.conversations.index
     */
    public function __invoke(Request $request): View
    {
        $account = null;
        $user = null;

        if ($request->has('account_id') && $request->account_id) {
            $account = WhatsAppAccount::with('user:id,name')->findOrFail($request->account_id);
        }

        if ($request->has('user_id') && $request->user_id) {
            $user = User::findOrFail($request->user_id);
        }

        $pageTitle = 'Toutes les Conversations WhatsApp';
        if ($account) {
            $pageTitle = "Conversations - {$account->session_name} ({$account->user->full_name})";
        } elseif ($user) {
            $pageTitle = "Conversations WhatsApp - {$user->full_name}";
        }

        return view('admin.whatsapp.conversations.index', [
            'account' => $account,
            'user' => $user,
            'pageTitle' => $pageTitle,
        ]);
    }
}
