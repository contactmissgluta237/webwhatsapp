<?php

namespace App\Livewire\Admin\CustomerDetails;

use App\Models\User;
use Livewire\Component;

class CustomerDetails extends Component
{
    public User $customer;
    public string $activeTab = 'overview';

    protected $queryString = ['activeTab'];

    public function mount(User $customer)
    {
        $this->customer = $customer;
    }

    public function render()
    {
        return view('livewire.admin.customer-details');
    }

    public function setActiveTab(string $tab)
    {
        $this->activeTab = $tab;
    }
}
