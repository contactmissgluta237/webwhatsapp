<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\WhatsApp\Account;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class RefreshController extends Controller
{
    public function __invoke(Request $request, WhatsAppAccount $account)
    {
        // Verify that the account belongs to the authenticated user
        if ($account->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this session');
        }

        return view('customer.whatsapp.refresh', compact('account'));
    }
}
