<?php

namespace App\Livewire\Admin\Packages\Forms;

use Illuminate\Foundation\Http\FormRequest;
use Livewire\Component;

abstract class AbstractPackageForm extends Component
{
    public string $name = '';
    public string $display_name = '';
    public string $description = '';
    public string $price = '';
    public string $currency = 'XAF';
    public string $messages_limit = '';
    public string $context_limit = '1000';
    public string $accounts_limit = '1';
    public string $products_limit = '0';
    public string $duration_days = '30';
    public bool $is_recurring = true;
    public bool $one_time_only = false;
    public bool $is_active = true;
    public array $features = [];
    public string $sort_order = '0';

    public string $promotional_price = '';
    public string $promotion_starts_at = '';
    public string $promotion_ends_at = '';
    public bool $promotion_is_active = false;

    public array $availableFeatures = [
        // Fonctionnalités supprimées : weekly_reports, priority_support, api_access
    ];

    public function rules(): array
    {
        // @phpstan-ignore-next-line
        return $this->customRequest()->rules();
    }

    public function messages(): array
    {
        return $this->customRequest()->messages();
    }

    abstract protected function customRequest(): FormRequest;

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);

        if ($propertyName === 'name') {
            $this->adjustDurationByPackageName();
        }
    }

    protected function adjustDurationByPackageName(): void
    {
        if (strtolower($this->name) === 'trial') {
            $this->duration_days = '7';
        } else {
            $this->duration_days = '30';
        }
    }

    public function getPromotionPreview()
    {
        $normalPrice = (float) $this->price;
        $promoPrice = (float) $this->promotional_price;

        if ($promoPrice > 0 && $promoPrice < $normalPrice && $this->promotion_is_active) {
            $discount = round((($normalPrice - $promoPrice) / $normalPrice) * 100);

            return [
                'show' => true,
                'text' => 'Prix barré: '.\App\Helpers\CurrencyHelper::formatUsd($normalPrice).' → Nouveau prix: '.\App\Helpers\CurrencyHelper::formatUsd($promoPrice)." (-{$discount}%)",
            ];
        }

        return ['show' => false, 'text' => ''];
    }

    abstract public function save();

    public function render()
    {
        return view('livewire.admin.packages.forms.package-form');
    }
}
