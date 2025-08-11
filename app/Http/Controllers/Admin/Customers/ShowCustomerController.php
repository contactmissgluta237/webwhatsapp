<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ShowCustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::CUSTOMERS_VIEW()->value.',customer');
    }

    /**
     * Display the specified customer.
     *
     * Route: GET /admin/customers/{customer}
     * Name: admin.customers.show
     */
    public function __invoke(Request $request, User $customer)
    {
        return view('admin.customers.show', compact('customer'));
    }
}
