<?php

namespace App\Http\Controllers\Payment\Callback;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ErrorController extends Controller
{
    public function __invoke(Request $request): View
    {
        $message = 'Une erreur est survenue lors de votre paiement. Veuillez réessayer.';
        $returnUrl = $this->getReturnUrl();

        $statusType = 'error';

        return view('payments.status', compact('message', 'returnUrl', 'statusType'));
    }

    private function getReturnUrl(): string
    {
        if (Auth::check()) {
            if (Auth::user()->isAdmin()) {
                return route('admin.transactions.index');
            }

            return route('customer.transactions.index');
        }

        return route('customer.transactions.index');
    }
}
