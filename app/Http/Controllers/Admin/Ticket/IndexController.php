<?php

namespace App\Http\Controllers\Admin\Ticket;

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
     * Display a listing of the tickets.
     *
     * Route: GET /admin/tickets
     * Name: admin.tickets.index
     */
    public function __invoke(Request $request)
    {
        return view('admin.tickets.index');
    }
}
