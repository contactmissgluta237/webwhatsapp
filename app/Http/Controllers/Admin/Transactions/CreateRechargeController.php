<?php

namespace App\Http\Controllers\Admin\Transactions;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CreateRechargeController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::TRANSACTIONS_CREATE_RECHARGE()->value);
    }

    /**
     * Show the form for creating a new recharge.
     *
     * Route: GET /admin/transactions/recharge
     * Name: admin.transactions.recharge
     */
    public function __invoke(): View
    {
        return view('admin.transactions.recharge', [
            'title' => 'Recharge Client',
        ]);
    }
}
