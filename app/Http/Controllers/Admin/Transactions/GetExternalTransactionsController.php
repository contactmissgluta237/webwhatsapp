<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Transactions;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class GetExternalTransactionsController extends Controller
{
    /**
     * Display external transactions listing page.
     *
     * @endpoint GET /admin/transactions
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
