<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class IndexController extends Controller
{
    /**
     * Display customer products listing page.
     *
     * @endpoint GET /customer/products
     */
    public function __invoke(Request $request): View
    {
        return view('customer.products.index');
    }
}
