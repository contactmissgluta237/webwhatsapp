<?php

namespace App\Http\Controllers\Customer\Ticket;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::TICKETS_VIEW()->value);
    }

    /**
     * Display a listing of the customer's tickets.
     *
     * Route: GET /customer/tickets
     * Name: customer.tickets.index
     */
    public function __invoke(Request $request)
    {
        return view('customer.tickets.index');
    }
}
