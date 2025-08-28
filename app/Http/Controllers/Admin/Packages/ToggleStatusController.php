<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Packages;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ToggleStatusController extends Controller
{
    public function __invoke(Request $request, Package $package): RedirectResponse
    {
        $newStatus = $request->input('is_active') === '1';

        $package->update([
            'is_active' => $newStatus,
        ]);

        $message = $newStatus
            ? "Le package '{$package->display_name}' a été activé avec succès."
            : "Le package '{$package->display_name}' a été désactivé avec succès.";

        return redirect()
            ->route('admin.packages.index')
            ->with('success', $message);
    }
}
