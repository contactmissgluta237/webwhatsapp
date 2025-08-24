<?php

declare(strict_types=1);

namespace App\Livewire\Customer\Products\Forms;

use App\DTOs\Customer\CreateProductDTO;
use App\Http\Requests\Customer\CreateProductRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class CreateProductForm extends AbstractProductForm
{
    public function mount(): void
    {
        $this->initializeForm();
        $this->is_active = true;
    }

    protected function initializeForm(): void
    {
        $this->allFiles = [];
        $this->newFiles = [];
        $this->existingFiles = [];
        $this->mediaFiles = [];

        Log::info('🆕 CreateProductForm initialized', [
            'user_id' => Auth::id(),
        ]);
    }

    protected function getInitialFiles(): array
    {
        return [];
    }

    public function save(): void
    {
        Log::info('🚀 CreateProductForm::save() START', [
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'is_active' => $this->is_active,
            'media_files_count' => count($this->allMediaFiles),
            'user_id' => Auth::id(),
        ]);

        try {
            $this->validate();
            Log::info('✅ Validation successful');

            $dto = new CreateProductDTO(
                title: $this->title,
                description: $this->description,
                price: $this->price,
                is_active: $this->is_active,
                media: $this->allMediaFiles,
                user_id: Auth::id()
            );

            $product = $this->productService->createProduct($dto);

            Log::info('🎉 Product created successfully', [
                'product_id' => $product->id,
                'media_count' => count($this->allMediaFiles),
            ]);

            session()->flash('success', 'Produit créé avec succès !');
            $this->redirectRoute('customer.products.index');

        } catch (\Exception $e) {
            Log::error('❌ Error creating product', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            session()->flash('error', 'Erreur lors de la création du produit.');
        }
    }

    protected function customRequest(): FormRequest
    {
        return new CreateProductRequest;
    }
}
