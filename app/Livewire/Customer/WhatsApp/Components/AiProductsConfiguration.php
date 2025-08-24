<?php

declare(strict_types=1);

namespace App\Livewire\Customer\WhatsApp\Components;

use App\Models\UserProduct;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class AiProductsConfiguration extends Component
{
    use AuthorizesRequests;

    public WhatsAppAccount $account;
    public string $searchTerm = '';
    public array $selectedToAdd = [];

    public function mount(WhatsAppAccount $account): void
    {
        $this->account = $account;
    }

    #[Computed]
    public function searchResults()
    {
        if (empty($this->searchTerm)) {
            return collect();
        }

        $linkedProductIds = $this->account->userProducts()->pluck('user_product_id')->toArray();

        return UserProduct::where('user_id', Auth::id())
            ->where('is_active', true)
            ->whereNotIn('id', $linkedProductIds)
            ->where(function ($q) {
                $q->where('title', 'like', '%'.$this->searchTerm.'%')
                    ->orWhere('description', 'like', '%'.$this->searchTerm.'%');
            })
            ->orderBy('title')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function linkedProducts()
    {
        return $this->account->userProducts()
            ->where('is_active', true)
            ->orderBy('title')
            ->get();
    }

    #[Computed]
    public function canAddMoreProducts(): bool
    {
        return $this->linkedProducts()->count() < 10;
    }

    public function toggleProductSelection(int $productId): void
    {
        if (in_array($productId, $this->selectedToAdd)) {
            $this->selectedToAdd = array_filter($this->selectedToAdd, fn ($id) => $id !== $productId);
        } else {
            $this->selectedToAdd[] = $productId;
        }
    }

    public function addSelectedProducts(): void
    {
        if (empty($this->selectedToAdd)) {
            $this->dispatch('show-toast', [
                'type' => 'warning',
                'message' => __('Aucun produit sélectionné'),
            ]);

            return;
        }

        $currentCount = $this->linkedProducts()->count();
        $newCount = count($this->selectedToAdd);

        if ($currentCount + $newCount > 10) {
            $this->dispatch('show-toast', [
                'type' => 'warning',
                'message' => __('Vous ne pouvez pas dépasser 10 produits par agent IA'),
            ]);

            return;
        }

        foreach ($this->selectedToAdd as $productId) {
            $this->account->userProducts()->attach($productId);
        }

        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => __(':count produit(s) ajouté(s) avec succès', ['count' => $newCount]),
        ]);

        $this->selectedToAdd = [];
        $this->searchTerm = '';
        $this->dispatch('products-updated');
    }

    public function removeProduct(int $productId): void
    {
        $this->account->userProducts()->detach($productId);

        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => __('Produit retiré avec succès'),
        ]);

        $this->dispatch('products-updated');
    }

    public function render()
    {
        return view('livewire.customer.whats-app.components.ai-products-configuration');
    }
}
