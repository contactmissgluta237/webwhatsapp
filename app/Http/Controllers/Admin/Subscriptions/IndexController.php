<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Subscriptions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndexController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('admin.subscriptions.index');
    }
}
