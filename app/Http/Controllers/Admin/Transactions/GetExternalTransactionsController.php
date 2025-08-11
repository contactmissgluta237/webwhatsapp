<?php

namespace App\Http\Controllers\Admin\Transactions;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class GetExternalTransactionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::TRANSACTIONS_VIEW_EXTERNAL()->value);
    }

    /**
     * Display a listing of the external transactions.
     *
     * Route: GET /admin/transactions
     * Name: admin.transactions.index
     */
    public function __invoke(): View
    {
        return view('admin.transactions.index', [
            'title' => 'Transactions Externes',
            'breadcrumbs' => [
                [
                    'name' => 'Tableau de bord',
                    'url' => route('admin.dashboard'),
                    'icon' => 'la la-dashboard',
                ],
                [
                    'name' => 'Transactions',
                    'url' => null,
                    'icon' => null,
                ],
            ],
        ]);
    }
}
