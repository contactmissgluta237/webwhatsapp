<?php

namespace App\Livewire\Admin\CustomerDetails;

use App\Models\User;
use Livewire\Component;

class Referrals extends Component
{
    public User $customer;

    public function render()
    {
        $totalReferralEarnings = $this->customer->totalReferralEarnings(); // Assuming this method exists on User model
        $referredUsers = $this->customer->referredUsers; // Assuming this is a relationship on User model

        return view('livewire.admin.customer-details.referrals', [
            'totalReferralEarnings' => $totalReferralEarnings,
            'referredUsers' => $referredUsers,
        ]);
    }
}
