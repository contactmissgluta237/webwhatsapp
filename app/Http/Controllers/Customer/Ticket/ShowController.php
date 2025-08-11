<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\Ticket;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

final class ShowController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionEnum::TICKETS_VIEW()->value.',ticket');
    }

    public function __invoke(Request $request, Ticket $ticket): mixed
    {
        $ticket->load(['messages.user', 'user', 'assignedTo']);

        return view('customer.tickets.show', compact('ticket'));
    }
}
