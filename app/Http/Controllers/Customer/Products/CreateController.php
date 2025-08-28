<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\Products;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class CreateController extends Controller
{
    /**
     * Display product creation form.
     *
     * @endpoint GET /customer/products/create
     */
    public function __invoke(): View
    {
        return view('customer.products.create');
    }
}
