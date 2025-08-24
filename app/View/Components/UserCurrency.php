<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Services\CurrencyService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

final class UserCurrency extends Component
{
    public function __construct(
        private readonly ?float $amount = null,
        private readonly bool $onlySymbol = false
    ) {}

    public function render(): View|Closure|string
    {
        $currencyService = app(CurrencyService::class);
        $user = Auth::user();
        $userCurrency = $currencyService->getUserCurrency($user);

        if ($this->onlySymbol) {
            $currencyInfo = $currencyService->getCurrencyInfo($userCurrency);

            return $currencyInfo['symbol'] ?? $userCurrency;
        }

        if ($this->amount !== null) {
            return $currencyService->formatPrice($this->amount, $userCurrency);
        }

        return $userCurrency;
    }
}
