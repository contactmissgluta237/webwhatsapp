<?php

declare(strict_types=1);

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppAccount;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class WhatsAppController extends Controller
{
    /**
     * Display the list of WhatsApp sessions for the authenticated user.
     *
     * Route: GET /whatsapp
     * Name: whatsapp.index
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        
        $sessions = WhatsAppAccount::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('whatsapp.index', compact('sessions'));
    }

    /**
     * Show the form for creating a new WhatsApp session.
     *
     * Route: GET /whatsapp/create
     * Name: whatsapp.create
     */
    public function create(Request $request): View
    {
        return view('whatsapp.create');
    }

    /**
     * Store a newly created WhatsApp session.
     *
     * Route: POST /whatsapp/store
     * Name: whatsapp.store
     */
    public function store(Request $request): RedirectResponse
    {
        // Cette méthode sera implémentée plus tard pour gérer la soumission du formulaire
        return redirect()->route('whatsapp.index')->with('success', 'Session créée avec succès.');
    }
}
