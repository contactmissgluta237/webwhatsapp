<?php

namespace App\Livewire\Customer;

use App\Services\CustomerDashboardMetricsService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CustomerDashboardMetrics extends Component
{
    public float $walletBalance = 0.0;
    public ?string $activePackageName = null;
    public ?string $packageExpirationDate = null;
    public int $messagesUsed = 0;
    public int $messagesLimit = 0;
    public int $remainingMessages = 0;
    public int $activeReferrals = 0;
    public float $commissionsEarned = 0.0;

    public function mount(CustomerDashboardMetricsService $metricsService): void
    {
        $this->loadMetrics($metricsService);
    }

    private function loadMetrics(CustomerDashboardMetricsService $metricsService): void
    {
        $customer = Auth::user();
        $metrics = $metricsService->getMetrics($customer);

        $this->walletBalance = $metrics->walletBalance;
        $this->activePackageName = $metrics->activePackageName;
        $this->packageExpirationDate = $metrics->packageExpirationDate;
        $this->messagesUsed = $metrics->messagesUsed;
        $this->messagesLimit = $metrics->messagesLimit;
        $this->remainingMessages = $metrics->remainingMessages;
        $this->activeReferrals = $metrics->activeReferrals;
        $this->commissionsEarned = $metrics->commissionsEarned;
    }

    public function render()
    {
        return view('livewire.customer.customer-dashboard-metrics');
    }
}
