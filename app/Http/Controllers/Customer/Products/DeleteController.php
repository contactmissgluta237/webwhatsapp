<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\Products;

use App\Http\Controllers\Controller;
use App\Models\UserProduct;
use Illuminate\Http\RedirectResponse;

final class DeleteController extends Controller
{
    public function __invoke(UserProduct $product): RedirectResponse
    {
        $product->delete();

        return back()->with('success', 'Produit supprimé avec succès.');
    }
}
