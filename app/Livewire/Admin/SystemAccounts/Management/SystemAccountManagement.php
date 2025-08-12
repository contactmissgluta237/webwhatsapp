<?php

namespace App\Livewire\Admin\SystemAccounts\Management;

use Livewire\Component;

class SystemAccountManagement extends Component
{
    public array $managementActions = [];

    public function mount(): void
    {
        $this->managementActions = [
            [
                'title' => 'Transactions',
                'description' => 'Voir toutes les transactions des comptes système.',
                'route' => 'admin.system-accounts.index',
                'buttonText' => 'Voir la liste',
                'buttonClass' => 'btn btn-whatsapp',
                'icon' => 'ti ti-list',
            ],
            [
                'title' => 'Recharge',
                'description' => 'Recharger un compte système.',
                'route' => 'admin.system-accounts.recharge',
                'buttonText' => 'Recharger',
                'buttonClass' => 'btn btn-success',
                'icon' => 'ti ti-plus',
            ],
            [
                'title' => 'Retrait',
                'description' => 'Retirer d\'un compte système.',
                'route' => 'admin.system-accounts.withdrawal',
                'buttonText' => 'Retirer',
                'buttonClass' => 'btn btn-danger',
                'icon' => 'ti ti-minus',
            ],
        ];
    }

    public function render()
    {
        return view('livewire.admin.system-account-management');
    }
}
