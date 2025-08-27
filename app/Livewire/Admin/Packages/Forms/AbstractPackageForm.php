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
    public string $duration_days = '';
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
        'weekly_reports' => 'Rapports hebdomadaires',
        'priority_support' => 'Support prioritaire',
        'api_access' => 'Accès API',
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
    }

    public function getPromotionPreview()
    {
        $normalPrice = (float) $this->price;
        $promoPrice = (float) $this->promotional_price;

        if ($promoPrice > 0 && $promoPrice < $normalPrice && $this->promotion_is_active) {
            $discount = round((($normalPrice - $promoPrice) / $normalPrice) * 100);

            return [
                'show' => true,
                'text' => 'Prix barré: '.number_format($normalPrice).' XAF → Nouveau prix: '.number_format($promoPrice)." XAF (-{$discount}%)",
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
