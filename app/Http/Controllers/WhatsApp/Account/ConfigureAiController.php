<?php

declare(strict_types=1);

namespace App\Http\Controllers\WhatsApp\Account;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ConfigureAiController extends Controller
{
    /**
     * Show the AI agent configuration page for a WhatsApp account.
     *
     * Route: GET /whatsapp/configure-ai/{account}
     * Name: whatsapp.configure-ai
     */
    public function __invoke(Request $request, WhatsAppAccount $account): View
    {
        // Ensure the account belongs to the authenticated user
        if ($account->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this WhatsApp account.');
        }

        return view('whatsapp.configure-ai', compact('account'));
    }
}
