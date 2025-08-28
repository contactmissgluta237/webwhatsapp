<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Transactions;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class GetInternalTransactionsController extends Controller
{
    /**
     * Display internal transactions listing page.
     *
     * @endpoint GET /admin/transactions/internal
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
