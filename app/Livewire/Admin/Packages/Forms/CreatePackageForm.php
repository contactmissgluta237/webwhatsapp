<?php

namespace App\Livewire\Admin\Packages\Forms;

use App\Http\Requests\Admin\Packages\StorePackageRequest;
use App\Models\Package;
use Illuminate\Foundation\Http\FormRequest;

class CreatePackageForm extends AbstractPackageForm
{
    public function save()
    {
        $this->validate();

        Package::create([
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description ?: null,
            'price' => (float) $this->price,
            'promotional_price' => $this->promotional_price ? (float) $this->promotional_price : null,
            'promotion_starts_at' => $this->promotion_starts_at ?: null,
            'promotion_ends_at' => $this->promotion_ends_at ?: null,
            'promotion_is_active' => $this->promotion_is_active,
            'currency' => $this->currency,
            'messages_limit' => (int) $this->messages_limit,
            'context_limit' => (int) $this->context_limit,
            'accounts_limit' => (int) $this->accounts_limit,
            'products_limit' => (int) $this->products_limit,
            'duration_days' => $this->duration_days ? (int) $this->duration_days : null,
            'is_recurring' => $this->is_recurring,
            'one_time_only' => $this->one_time_only,
            'is_active' => $this->is_active,
            'features' => $this->features ?: null,
            'sort_order' => (int) $this->sort_order,
        ]);

        session()->flash('success', 'Package créé avec succès.');

        return redirect()->route('admin.packages.index');
    }

    protected function customRequest(): FormRequest
    {
        return new StorePackageRequest;
    }

    public function render()
    {
        return view('livewire.admin.packages.forms.create-package-form');
    }
}
