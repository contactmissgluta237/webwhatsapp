<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Packages;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\View\View;

class EditController extends Controller
{
    public function __invoke(Package $package): View
    {
        return view('admin.packages.edit', compact('package'));
    }
}
