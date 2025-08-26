<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\WhatsApp\Conversations;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Display the list of conversations for a WhatsApp account.
 */
final class IndexController extends Controller
{
    /**
     * Display the list of conversations for the specified WhatsApp account.
     */
    public function __invoke(Request $request, WhatsAppAccount $account): View
    {
        // Log pour débugger
        Log::info('IndexController called', [
            'account_id' => $account->id,
            'user_id' => auth()->id(),
            'account_user_id' => $account->user_id,
        ]);

        return view('customer.whatsapp.conversations.index', compact('account'));
    }
}
