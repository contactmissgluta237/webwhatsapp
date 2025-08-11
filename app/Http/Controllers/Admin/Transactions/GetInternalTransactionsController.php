<?php

namespace App\Http\Controllers\Admin\Transactions;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class GetInternalTransactionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::TRANSACTIONS_VIEW_INTERNAL()->value);
    }

    /**
     * Display a listing of the internal transactions.
     *
     * Route: GET /admin/transactions/internal
     * Name: admin.transactions.internal
     */
    public function __invoke(): View
    {
        return view('admin.transactions.internal-index', [
            'title' => 'Mouvements de Compte Internes',
            'breadcrumbs' => [
                [
                    'name' => 'Tableau de bord',
                    'url' => route('admin.dashboard'),
                    'icon' => 'la la-dashboard',
                ],
                [
                    'name' => 'Transactions',
                    'url' => route('admin.transactions.index'),
                    'icon' => null,
                ],
                [
                    'name' => 'Mouvements de Compte Internes',
                    'url' => null,
                    'icon' => null,
                ],
            ],
        ]);
    }
}
