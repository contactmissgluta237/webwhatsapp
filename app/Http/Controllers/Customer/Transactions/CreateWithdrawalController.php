<?php

namespace App\Http\Controllers\Customer\Transactions;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CreateWithdrawalController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::TRANSACTIONS_CREATE_CUSTOMER_WITHDRAWAL()->value);
    }

    /**
     * Show the form for creating a new customer withdrawal.
     *
     * Route: GET /customer/transactions/withdrawal
     * Name: customer.transactions.withdrawal
     */
    public function __invoke(): View
    {
        return view('customer.transactions.withdrawal');
    }
}
