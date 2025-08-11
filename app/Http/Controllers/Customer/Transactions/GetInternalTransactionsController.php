<?php

namespace App\Http\Controllers\Customer\Transactions;

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
     * Route: GET /customer/transactions/internal
     * Name: customer.transactions.internal
     */
    public function __invoke(): View
    {
        return view('customer.transactions.internal-index');
    }
}
