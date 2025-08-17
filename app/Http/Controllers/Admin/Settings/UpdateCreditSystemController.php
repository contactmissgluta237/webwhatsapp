<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCreditSystemRequest;
use App\Services\CreditSystemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

final class UpdateCreditSystemController extends Controller
{
    public function __construct(
        private readonly CreditSystemService $creditSystemService
    ) {
        $this->middleware('can:' . PermissionEnum::SETTINGS_VIEW()->value);
    }

    public function __invoke(UpdateCreditSystemRequest $request): RedirectResponse
    {
        $newCost = (float) $request->validated()['message_cost'];
        $oldCost = $this->creditSystemService->getMessageCost();

        if ($this->creditSystemService->updateMessageCost($newCost)) {
            Log::info('[ADMIN] AI message cost updated', [
                'admin_user_id' => auth()->id(),
                'old_cost' => $oldCost,
                'new_cost' => $newCost,
            ]);

            session()->flash('success', 'Message cost updated successfully.');
        } else {
            Log::error('[ADMIN] Failed to update AI message cost', [
                'admin_user_id' => auth()->id(),
                'attempted_cost' => $newCost,
            ]);

            session()->flash('error', 'Error updating message cost.');
        }

        return redirect()->route('admin.settings.credit-system.index');
    }
}