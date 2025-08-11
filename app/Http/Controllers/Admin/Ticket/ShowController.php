<?php

namespace App\Http\Controllers\Admin\Ticket;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class ShowController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::TICKETS_VIEW()->value.',ticket');
    }

    /**
     * Display the specified ticket.
     *
     * Route: GET /admin/tickets/{ticket}
     * Name: admin.tickets.show
     */
    public function __invoke(Request $request, Ticket $ticket)
    {
        return view('admin.tickets.show', compact('ticket'));
    }
}
