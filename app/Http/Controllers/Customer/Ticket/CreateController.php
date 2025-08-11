<?php

namespace App\Http\Controllers\Customer\Ticket;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CreateController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::TICKETS_CREATE()->value);
    }

    /**
     * Show the form for creating a new ticket.
     *
     * Route: GET /customer/tickets/create
     * Name: customer.tickets.create
     */
    public function __invoke(Request $request)
    {
        return view('customer.tickets.create');
    }
}
