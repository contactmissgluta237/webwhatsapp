<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\Products;

use App\Http\Controllers\Controller;
use App\Models\UserProduct;

final class EditController extends Controller
{
    public function __invoke(UserProduct $product)
    {
        $this->authorize('update', $product);

        return view('customer.products.edit', compact('product'));
    }
}
