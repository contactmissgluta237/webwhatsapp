<?php

namespace App\Http\Controllers\Payment\Callback;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SuccessController extends Controller
{
    public function __invoke(Request $request): View
    {
        $message = 'Votre paiement a été effectué avec succès.';
        $returnUrl = $this->getReturnUrl();

        $statusType = 'success';

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
