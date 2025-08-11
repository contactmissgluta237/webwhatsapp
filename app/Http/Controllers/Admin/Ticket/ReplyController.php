<?php

namespace App\Http\Controllers\Admin\Ticket;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class ReplyController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::TICKETS_REPLY()->value.',ticket');
    }

    /**
     * Show the form for replying to a ticket.
     *
     * Route: GET /admin/tickets/{ticket}/reply
     * Name: admin.tickets.reply
     */
    public function __invoke(Request $request, Ticket $ticket)
    {
        return view('admin.tickets.reply', compact('ticket'));
    }
}
