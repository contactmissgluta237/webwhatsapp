<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\WhatsApp\Account;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NotificationsController extends Controller
{
    /**
     * Show the notifications configuration page for a WhatsApp account.
     *
     * Route: GET /whatsapp/notifications/{account}
     * Name: whatsapp.notifications.config
     */
    public function __invoke(Request $request, WhatsAppAccount $account): View
    {
        // Ensure the account belongs to the authenticated user
        if ($account->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this WhatsApp account.');
        }

        return view('customer.whatsapp.notifications', compact('account'));
    }
}
