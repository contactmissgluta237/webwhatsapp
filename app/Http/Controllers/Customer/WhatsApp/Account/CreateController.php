<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\WhatsApp\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CreateController extends Controller
{
    /**
     * Show the form for creating a new WhatsApp session.
     *
     * Route: GET /whatsapp/create
     * Name: whatsapp.create
     */
    public function __invoke(Request $request): View
    {
        return view('customer.whatsapp.create');
    }
}
