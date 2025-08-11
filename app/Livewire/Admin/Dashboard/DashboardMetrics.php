<?php

namespace App\Livewire\Admin\Dashboard;

use App\Services\AdminDashboardMetricsService;
use Carbon\Carbon;
use Livewire\Component;

class DashboardMetrics extends Component
{
    public ?string $startDate = null;
    public ?string $endDate = null;

    public int $registeredUsers = 0;
    public float $totalWithdrawals = 0.0;
    public float $totalRecharges = 0.0;
    public float $companyProfit = 0.0;

    public function mount(AdminDashboardMetricsService $metricsService): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->loadMetrics($metricsService);
    }

    public function updatedStartDate(AdminDashboardMetricsService $metricsService): void
    {
        $this->loadMetrics($metricsService);
    }

    public function updatedEndDate(AdminDashboardMetricsService $metricsService): void
    {
        $this->loadMetrics($metricsService);
    }

    public function applyFilter(AdminDashboardMetricsService $metricsService): void
    {
        $this->loadMetrics($metricsService);
        $this->dispatch('metrics-updated', [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
    }

    private function loadMetrics(AdminDashboardMetricsService $metricsService): void
    {
        $startDate = $this->startDate ? Carbon::parse($this->startDate) : null;
        $endDate = $this->endDate ? Carbon::parse($this->endDate) : null;

        $metrics = $metricsService->getMetrics($startDate, $endDate);

        $this->registeredUsers = $metrics->registeredUsers;
        $this->totalWithdrawals = $metrics->totalWithdrawals;
        $this->totalRecharges = $metrics->totalRecharges;
        $this->companyProfit = $metrics->companyProfit;
    }

    public function render()
    {
        return view('livewire.admin.dashboard-metrics');
    }
}
