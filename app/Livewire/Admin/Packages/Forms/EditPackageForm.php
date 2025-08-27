<?php

namespace App\Livewire\Admin\Packages\Forms;

use App\Http\Requests\Admin\Packages\UpdatePackageRequest;
use App\Models\Package;
use Illuminate\Foundation\Http\FormRequest;

class EditPackageForm extends AbstractPackageForm
{
    public Package $package;

    public function mount(Package $package)
    {
        $this->package = $package;

        $this->name = $package->name;
        $this->display_name = $package->display_name;
        $this->description = $package->description ?? '';
        $this->price = (string) $package->price;
        $this->promotional_price = $package->promotional_price ? (string) $package->promotional_price : '';
        $this->promotion_starts_at = $package->promotion_starts_at ? $package->promotion_starts_at->format('Y-m-d\TH:i') : '';
        $this->promotion_ends_at = $package->promotion_ends_at ? $package->promotion_ends_at->format('Y-m-d\TH:i') : '';
        $this->promotion_is_active = $package->promotion_is_active;
        $this->currency = $package->currency;
        $this->messages_limit = (string) $package->messages_limit;
        $this->context_limit = (string) $package->context_limit;
        $this->accounts_limit = (string) $package->accounts_limit;
        $this->products_limit = (string) $package->products_limit;
        $this->duration_days = $package->duration_days ? (string) $package->duration_days : '';
        $this->is_recurring = $package->is_recurring;
        $this->one_time_only = $package->one_time_only;
        $this->is_active = $package->is_active;
        $this->features = $package->features ?? [];
        $this->sort_order = (string) $package->sort_order;
    }

    public function save()
    {
        $this->validate();

        $this->package->update([
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

        session()->flash('success', 'Package mis à jour avec succès.');

        return redirect()->route('admin.packages.index');
    }

    protected function customRequest(): FormRequest
    {
        $request = new UpdatePackageRequest;
        $request->setRouteResolver(function () {
            return app('router')->current();
        });

        return $request;
    }

    public function render()
    {
        return view('livewire.admin.packages.forms.edit-package-form');
    }
}
