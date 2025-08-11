<?php

namespace App\Http\Controllers\Customer\Transactions;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
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
     * Route: GET /customer/transactions
     * Name: customer.transactions.index
     */
    public function __invoke(): View
    {
        $walletBalance = Auth::user()->wallet?->balance ?? 0;

        return view('customer.transactions.index', compact('walletBalance'));
    }
}
