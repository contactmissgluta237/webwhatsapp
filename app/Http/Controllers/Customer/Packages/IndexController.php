<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\Packages;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndexController extends Controller
{
    public function __invoke(Request $request): View
    {
        $packages = Package::active()
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        $user = $request->user();
        $currentSubscription = $user->activeSubscription;

        return view('customer.packages.index', compact('packages', 'currentSubscription'));
    }
}
