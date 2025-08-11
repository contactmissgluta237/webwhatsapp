<?php

namespace App\Http\Controllers\Admin\SystemAccounts;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::SYSTEM_ACCOUNTS_WITHDRAW()->value);
    }

    /**
     * Display the form for withdrawing from a system account.
     *
     * Route: GET /admin/system-accounts/withdrawal
     * Name: admin.system-accounts.withdrawal
     */
    public function __invoke(): View
    {
        return view('admin.system_accounts.withdrawal', [
            'title' => 'Retirer d\'un Compte Système',
            'breadcrumbs' => [
                ['name' => 'Tableau de bord', 'url' => route('admin.dashboard')],
                ['name' => 'Comptes Système', 'url' => route('admin.system-accounts.index')],
                ['name' => 'Retrait', 'url' => null],
            ],
        ]);
    }
}
