<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\Products;

use App\Http\Controllers\Controller;

final class CreateController extends Controller
{
    public function __invoke()
    {
        return view('customer.products.create');
    }
}
