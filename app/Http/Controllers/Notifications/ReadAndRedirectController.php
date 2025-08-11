<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class ReadAndRedirectController extends Controller
{
    public function __invoke(Request $request, string $notificationId): RedirectResponse
    {
        $notification = Auth::user()->notifications()->findOrFail($notificationId);

        $notification->markAsRead();

        if (isset($notification->data['url'])) {
            return redirect($notification->data['url']);
        }

        return redirect()->back();
    }
}
