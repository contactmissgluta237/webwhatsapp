<?php

declare(strict_types=1);

namespace App\Livewire\Customer;

use App\Models\UserProduct;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class ProductManager extends Component
{
    use AuthorizesRequests;

    // Form properties
    public string $title = '';
    public string $description = '';
    public int $price = 0;
    public bool $showForm = false;
    public ?UserProduct $editingProduct = null;

    protected array $rules = [
        'title' => 'required|string|min:3|max:255',
        'description' => 'required|string|min:10|max:1000',
        'price' => 'required|integer|min:1',
    ];

    #[Computed]
    public function products()
    {
        return UserProduct::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $productId): void
    {
        $product = UserProduct::where('user_id', Auth::id())->findOrFail($productId);
        
        $this->editingProduct = $product;
        $this->title = $product->title;
        $this->description = $product->description;
        $this->price = (int) $product->price;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingProduct) {
            // Update existing product
            $this->editingProduct->update([
                'title' => $this->title,
                'description' => $this->description,
                'price' => $this->price,
            ]);
        } else {
            // Create new product
            UserProduct::create([
                'user_id' => Auth::id(),
                'title' => $this->title,
                'description' => $this->description,
                'price' => $this->price,
                'is_active' => true,
            ]);
        }

        $this->dispatch('product-saved');
        $this->resetForm();
    }

    public function toggleStatus(int $productId): void
    {
        $product = UserProduct::where('user_id', Auth::id())->findOrFail($productId);
        
        $product->update([
            'is_active' => !$product->is_active,
        ]);

        $this->dispatch('product-status-updated');
    }

    public function delete(int $productId): void
    {
        $product = UserProduct::where('user_id', Auth::id())->findOrFail($productId);
        $product->delete();

        $this->dispatch('product-deleted');
    }

    public function resetForm(): void
    {
        $this->title = '';
        $this->description = '';
        $this->price = 0;
        $this->showForm = false;
        $this->editingProduct = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.customer.product-manager');
    }
}