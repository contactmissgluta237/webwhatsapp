<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\Products;

use App\Http\Controllers\Controller;
use App\Models\UserProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ToggleStatusController extends Controller
{
    public function __invoke(Request $request, UserProduct $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $isActive = $request->boolean('is_active');

        $product->update(['is_active' => $isActive]);

        $status = $isActive ? 'activé' : 'désactivé';

        return back()->with('success', "Produit {$status} avec succès.");
    }
}
