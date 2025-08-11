<?php

namespace App\Http\Controllers\Customer\Transactions;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CreateRechargeController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::TRANSACTIONS_CREATE_CUSTOMER_RECHARGE()->value);
    }

    /**
     * Show the form for creating a new customer recharge.
     *
     * Route: GET /customer/transactions/recharge
     * Name: customer.transactions.recharge
     */
    public function __invoke(): View
    {
        return view('customer.transactions.recharge');
    }
}
