<?php

namespace App\Livewire\Admin\SystemAccounts\Management;

use App\Services\AdminDashboardMetricsService;
use Livewire\Attributes\On;
use Livewire\Component;

class SystemAccountBalances extends Component
{
    public array $systemAccounts = [];

    public function mount(AdminDashboardMetricsService $metricsService): void
    {
        $this->loadSystemAccounts($metricsService);
    }

    #[On('metrics-updated')]
    public function refreshBalances(AdminDashboardMetricsService $metricsService): void
    {
        $this->loadSystemAccounts($metricsService);
    }

    private function loadSystemAccounts(AdminDashboardMetricsService $metricsService): void
    {
        $systemAccountsCollection = $metricsService->getSystemAccountsBalance();

        $this->systemAccounts = $systemAccountsCollection
            ->map(fn ($account) => [
                'type' => $account->type,
                'balance' => $account->balance,
                'icon' => $account->icon,
                'badge' => $account->badge,
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.admin.system-account-balances');
    }
}
