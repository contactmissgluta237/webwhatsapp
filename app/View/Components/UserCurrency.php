<?php

declare(strict_types=1);

namespace App\View\Components;

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
        $user = Auth::user();

        if ($this->onlySymbol) {
            return '$';
        }

        if ($this->amount !== null) {
            return \App\Helpers\CurrencyHelper::formatUsd($this->amount);
        }

        return 'USD';
    }
}
