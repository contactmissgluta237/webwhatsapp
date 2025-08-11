<?php

declare(strict_types=1);

namespace App\Http\Controllers\WhatsApp\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class IndexController extends Controller
{
    /**
     * Display the WhatsApp dashboard.
     *
     * Route: GET /whatsapp
     * Name: whatsapp.dashboard
     */
    public function __invoke(Request $request): View
    {
        /** @var view-string $viewName */
        $viewName = 'whatsapp.dashboard';

        return view($viewName);
    }
}
