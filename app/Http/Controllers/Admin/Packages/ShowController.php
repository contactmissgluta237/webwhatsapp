<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Packages;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Contracts\View\View;

class ShowController extends Controller
{
    public function __invoke(Package $package): View
    {
        $package->load(['subscriptions' => function ($query) {
            $query->with(['user'])
                ->latest()
                ->limit(10);
        }]);

        return view('admin.packages.show', compact('package'));
    }
}
