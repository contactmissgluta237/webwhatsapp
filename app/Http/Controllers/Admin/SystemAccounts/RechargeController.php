<?php

namespace App\Http\Controllers\Admin\SystemAccounts;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class RechargeController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::SYSTEM_ACCOUNTS_RECHARGE()->value);
    }

    /**
     * Display the form for recharging a system account.
     *
     * Route: GET /admin/system-accounts/recharge
     * Name: admin.system-accounts.recharge
     */
    public function __invoke(): View
    {
        return view('admin.system_accounts.recharge', [
            'title' => 'Recharger un Compte Système',
            'breadcrumbs' => [
                ['name' => 'Tableau de bord', 'url' => route('admin.dashboard')],
                ['name' => 'Comptes Système', 'url' => route('admin.system-accounts.index')],
                ['name' => 'Recharge', 'url' => null],
            ],
        ]);
    }
}
