<?php

declare(strict_types=1);

namespace App\Livewire\WhatsApp\Components;

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
    public array $selectedProductIds = [];

    public function mount(WhatsAppAccount $account): void
    {
        $this->account = $account;
        $this->loadSelectedProducts();
    }

    #[Computed]
    public function availableProducts()
    {
        $query = Auth::user()->userProducts()->where('is_active', true);
        
        if (!empty($this->searchTerm)) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $this->searchTerm . '%');
            });
        }
        
        return $query->orderBy('title')->get();
    }

    #[Computed]
    public function linkedProducts()
    {
        return $this->account->linkedProducts()
            ->where('is_active', true)
            ->orderBy('title')
            ->get();
    }

    #[Computed]
    public function canAddMoreProducts(): bool
    {
        return $this->linkedProducts->count() < 10;
    }

    #[Computed]
    public function remainingSlots(): int
    {
        return max(0, 10 - $this->linkedProducts->count());
    }

    public function addProduct(int $productId): void
    {
        if (!$this->canAddMoreProducts) {
            $this->dispatch('show-toast', [
                'type' => 'warning',
                'message' => __('Vous avez atteint la limite de 10 produits par agent IA'),
            ]);
            return;
        }

        $product = UserProduct::where('id', $productId)
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->first();

        if (!$product) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('Produit non trouvé ou inactif'),
            ]);
            return;
        }

        if ($this->account->linkedProducts()->where('user_product_id', $productId)->exists()) {
            $this->dispatch('show-toast', [
                'type' => 'warning',
                'message' => __('Ce produit est déjà lié à cet agent'),
            ]);
            return;
        }

        $this->account->linkedProducts()->attach($productId);

        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => __('Produit ajouté avec succès'),
        ]);

        $this->dispatch('products-updated');
    }

    public function removeProduct(int $productId): void
    {
        $this->account->linkedProducts()->detach($productId);

        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => __('Produit retiré avec succès'),
        ]);

        $this->dispatch('products-updated');
    }

    public function updatedSearchTerm(): void
    {
        // Reactivity will handle the search automatically
    }

    private function loadSelectedProducts(): void
    {
        $this->selectedProductIds = $this->account->linkedProducts()->pluck('user_product_id')->toArray();
    }

    public function render()
    {
        return view('livewire.whats-app.components.ai-products-configuration');
    }
}