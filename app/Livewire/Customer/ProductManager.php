<?php

declare(strict_types=1);

namespace App\Livewire\Customer;

use App\Models\UserProduct;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

final class ProductManager extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public bool $showForm = false;
    public ?UserProduct $editingProduct = null;

    #[Rule('required|string|max:100')]
    public string $title = '';

    #[Rule('required|string|max:1000')]
    public string $description = '';

    #[Rule('required|numeric|min:0')]
    public float $price = 0;

    #[Rule('nullable|array|max:5')]
    #[Rule('images.*', 'image|max:2048')]
    public array $images = [];

    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorize('viewAny', UserProduct::class);
    }

    #[Computed]
    public function products()
    {
        return Auth::user()->userProducts()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function openCreateForm(): void
    {
        $this->authorize('create', UserProduct::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(UserProduct $product): void
    {
        $this->authorize('update', $product);
        
        $this->editingProduct = $product;
        $this->title = $product->title;
        $this->description = $product->description;
        $this->price = $product->price;
        $this->is_active = $product->is_active;
        $this->images = [];
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingProduct) {
            $this->authorize('update', $this->editingProduct);
            $product = $this->editingProduct;
        } else {
            $this->authorize('create', UserProduct::class);
            $product = new UserProduct();
            $product->user_id = Auth::id();
        }

        $product->fill([
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'is_active' => $this->is_active,
        ]);

        $product->save();

        if (!empty($this->images)) {
            foreach ($this->images as $image) {
                $product->addMediaFromRequest($image)
                    ->toMediaCollection('images');
            }
        }

        $this->dispatch('product-saved', [
            'type' => 'success',
            'message' => $this->editingProduct 
                ? __('Produit mis à jour avec succès')
                : __('Produit créé avec succès'),
        ]);

        $this->resetForm();
    }

    public function delete(UserProduct $product): void
    {
        $this->authorize('delete', $product);
        
        $product->delete();
        
        $this->dispatch('product-deleted', [
            'type' => 'success',
            'message' => __('Produit supprimé avec succès'),
        ]);
    }

    public function toggleStatus(UserProduct $product): void
    {
        $this->authorize('update', $product);
        
        $product->update(['is_active' => !$product->is_active]);
        
        $this->dispatch('product-status-updated', [
            'type' => 'success',
            'message' => $product->is_active 
                ? __('Produit activé') 
                : __('Produit désactivé'),
        ]);
    }

    public function removeImage(UserProduct $product, int $mediaId): void
    {
        $this->authorize('update', $product);
        
        $media = $product->getMedia('images')->find($mediaId);
        if ($media) {
            $media->delete();
            
            $this->dispatch('image-removed', [
                'type' => 'success',
                'message' => __('Image supprimée'),
            ]);
        }
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->editingProduct = null;
        $this->title = '';
        $this->description = '';
        $this->price = 0;
        $this->images = [];
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.customer.product-manager');
    }
}