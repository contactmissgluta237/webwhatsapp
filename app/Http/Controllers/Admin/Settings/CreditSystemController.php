<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Services\CreditSystemService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CreditSystemController extends Controller
{
    public function __construct(
        private readonly CreditSystemService $creditSystemService
    ) {
        $this->middleware('can:' . PermissionEnum::SETTINGS_VIEW()->value);
    }

    public function __invoke(Request $request): View
    {
        $currentCost = $this->creditSystemService->getMessageCost();

        return view('admin.settings.credit-system', [
            'currentCost' => $currentCost,
        ]);
    }
}