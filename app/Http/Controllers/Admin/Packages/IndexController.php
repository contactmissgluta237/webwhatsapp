<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Packages;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndexController extends Controller
{
    public function __invoke(Request $request): View
    {
        $packages = Package::withCount('subscriptions')
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return view('admin.packages.index', compact('packages'));
    }
}
