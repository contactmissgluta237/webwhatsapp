<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\WhatsApp\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class IndexController extends Controller
{
    /**
     * Display a listing of all WhatsApp accounts.
     *
     * Route: GET /admin/whatsapp/accounts
     * Name: admin.whatsapp.accounts.index
     */
    public function __invoke(Request $request): View
    {
        Log::info('WhatsApp Accounts Index accessed', [
            'user_id' => Auth::id(),
            'request_params' => $request->all(),
        ]);

        return view('admin.whatsapp.accounts.index', [
            'pageTitle' => 'Tous les Comptes WhatsApp',
        ]);
    }
}
