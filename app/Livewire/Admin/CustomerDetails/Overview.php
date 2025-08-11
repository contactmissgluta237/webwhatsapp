<?php

namespace App\Livewire\Admin\CustomerDetails;

use App\Models\User;
use Livewire\Component;

class Overview extends Component
{
    public User $customer;

    public function render()
    {
        return view('livewire.admin.customer-details.overview');
    }
}
