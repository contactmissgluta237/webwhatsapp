<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\WhatsApp\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class IndexController extends Controller
{
    /**
     * Display the list of WhatsApp sessions for the authenticated user.
     *
     * Route: GET /whatsapp
     * Name: whatsapp.index
     */
    public function __invoke(Request $request): View
    {
        return view('customer.whatsapp.index');
    }
}
