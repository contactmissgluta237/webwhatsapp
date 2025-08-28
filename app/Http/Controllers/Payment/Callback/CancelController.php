<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payment\Callback;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class CancelController extends Controller
{
    /**
     * Display payment cancellation page.
     *
     * @endpoint GET /payment/cancel
     */
    public function __invoke(Request $request): View
    {
        $message = 'Votre paiement a été annulé.';
        $returnUrl = $this->getReturnUrl();
        $statusType = 'cancel';

        return view('payments.status', compact('message', 'returnUrl', 'statusType'));
    }

    private function getReturnUrl(): string
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            if ($user->isAdmin()) {
                return route('admin.transactions.index');
            }

            return route('customer.transactions.index');
        }

        return route('customer.transactions.index');
    }
}
