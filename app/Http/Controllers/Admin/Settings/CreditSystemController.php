<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Services\CreditSystemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class CreditSystemController extends Controller
{
    public function __construct(
        private readonly CreditSystemService $creditSystemService
    ) {
        $this->middleware('can:' . PermissionEnum::SETTINGS_VIEW()->value);
    }

    /**
     * Display credit system settings
     */
    public function index(Request $request): View
    {
        $currentCost = $this->creditSystemService->getMessageCost();

        return view('admin.settings.credit-system', [
            'currentCost' => $currentCost,
        ]);
    }

    /**
     * Update credit system settings
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message_cost' => [
                'required',
                'numeric',
                'min:0',
                'max:1000'
            ],
        ], [
            'message_cost.required' => 'Le coût par message est requis.',
            'message_cost.numeric' => 'Le coût par message doit être un nombre.',
            'message_cost.min' => 'Le coût par message doit être positif.',
            'message_cost.max' => 'Le coût par message ne peut pas dépasser 1000 FCFA.',
        ]);

        $newCost = (float) $validated['message_cost'];
        $oldCost = $this->creditSystemService->getMessageCost();

        if ($this->creditSystemService->updateMessageCost($newCost)) {
            Log::info('[ADMIN] Coût message IA mis à jour', [
                'admin_user_id' => auth()->id(),
                'old_cost' => $oldCost,
                'new_cost' => $newCost,
            ]);

            session()->flash('success', 'Coût par message mis à jour avec succès.');
        } else {
            Log::error('[ADMIN] Échec mise à jour coût message IA', [
                'admin_user_id' => auth()->id(),
                'attempted_cost' => $newCost,
            ]);

            session()->flash('error', 'Erreur lors de la mise à jour du coût par message.');
        }

        return redirect()->route('admin.settings.credit-system.index');
    }
}