<?php

namespace App\Http\Controllers\Admin\SystemAccounts;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class GetTransactionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::SYSTEM_ACCOUNTS_VIEW_TRANSACTIONS()->value);
    }

    /**
     * Display a listing of the system account transactions.
     *
     * Route: GET /admin/system-accounts
     * Name: admin.system-accounts.index
     */
    public function __invoke(): View
    {
        return view('admin.system_accounts.index', [
            'title' => 'Transactions des Comptes Système',
            'breadcrumbs' => [
                ['name' => 'Tableau de bord', 'url' => route('admin.dashboard')],
                ['name' => 'Comptes Système', 'url' => null],
                ['name' => 'Transactions', 'url' => null],
            ],
        ]);
    }
}
