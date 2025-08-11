<?php

namespace App\Http\Controllers\Admin\Transactions;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CreateWithdrawalController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::TRANSACTIONS_CREATE_WITHDRAWAL()->value);
    }

    /**
     * Show the form for creating a new withdrawal.
     *
     * Route: GET /admin/transactions/withdrawal
     * Name: admin.transactions.withdrawal
     */
    public function __invoke(): View
    {
        return view('admin.transactions.withdrawal', [
            'title' => 'Demander un Retrait',
            'breadcrumbs' => [
                ['name' => 'Tableau de bord', 'url' => route('admin.dashboard')],
                ['name' => 'Transactions', 'url' => route('admin.transactions.index')],
                ['name' => 'Demander un Retrait', 'url' => null],
            ],
        ]);
    }
}
