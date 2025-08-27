<?php

declare(strict_types=1);

namespace App\Livewire\Customer\Products\Forms;

use App\DTOs\Customer\UpdateProductDTO;
use App\Http\Requests\Customer\UpdateProductRequest;
use App\Models\UserProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class EditProductForm extends AbstractProductForm
{
    public UserProduct $product;

    public function mount(UserProduct $product): void
    {
        $this->product = $product;
        $this->loadProductData();
        $this->loadExistingMedias();

        Log::info('✏️ EditProductForm initialized', [
            'product_id' => $this->product->id,
            'existing_medias_count' => count($this->allMediaFiles),
        ]);
    }

    private function loadProductData(): void
    {
        $this->title = $this->product->title;
        $this->description = $this->product->description ?? '';
        $this->price = (float) $this->product->price;
        $this->is_active = $this->product->is_active;
    }

    private function loadExistingMedias(): void
    {
        $this->allMediaFiles = $this->product->getMedia('medias')->map(function (Media $mediaItem): array {
            return [
                'id' => 'existing_'.$mediaItem->id,
                'name' => $mediaItem->name,
                'file_name' => $mediaItem->file_name,
                'mime_type' => $mediaItem->mime_type,
                'size' => $mediaItem->size,
                'url' => $mediaItem->getUrl(),
                'media_id' => $mediaItem->id,
                'extension' => pathinfo($mediaItem->file_name, PATHINFO_EXTENSION),
                'is_existing' => true,
            ];
        })->toArray();
    }

    protected function getInitialFiles(): array
    {
        return $this->allMediaFiles;
    }

    public function removeMediaFile(int $index): void
    {
        if (isset($this->allMediaFiles[$index])) {
            $file = $this->allMediaFiles[$index];

            // Si c'est un média existant, le marquer pour suppression
            if (is_object($file) && property_exists($file, 'is_existing') && $file->is_existing) {
                Log::info('�️ Existing media marked for removal', [
                    'media_id' => $file->media_id,
                    'file_name' => $file->file_name,
                ]);
            }

            array_splice($this->allMediaFiles, $index, 1);
            Log::info('🗑️ Media file removed from preview', [
                'index' => $index,
                'remaining' => count($this->allMediaFiles),
            ]);
        }
    }

    public function save(): void
    {
        Log::info('🔄 EditProductForm::save() START', [
            'product_id' => $this->product->id,
            'title' => $this->title,
            'total_files_count' => count($this->allMediaFiles),
        ]);

        try {
            $this->validate();
            Log::info('✅ Validation successful');

            // Séparer les fichiers nouveaux des existants
            $newFiles = [];
            $existingMediaIds = [];

            foreach ($this->allMediaFiles as $file) {
                if (is_array($file) && isset($file['is_existing']) && $file['is_existing']) {
                    $existingMediaIds[] = $file['media_id'];
                } else {
                    $newFiles[] = $file;
                }
            }

            Log::info('🔍 Files analysis', [
                'total_files' => count($this->allMediaFiles),
                'existing_files' => count($existingMediaIds),
                'new_files' => count($newFiles),
                'existing_media_ids' => $existingMediaIds,
            ]);

            // Supprimer les médias qui ne sont plus dans la liste
            $currentMediaIds = $this->product->getMedia('medias')->pluck('id')->toArray();
            $mediasToDelete = array_diff($currentMediaIds, $existingMediaIds);

            foreach ($mediasToDelete as $mediaId) {
                $this->product->deleteMedia($mediaId);
                Log::info('🗑️ Media deleted', ['media_id' => $mediaId]);
            }

            // Mettre à jour les données du produit
            $dto = new UpdateProductDTO(
                title: $this->title,
                description: $this->description,
                price: $this->price,
                is_active: $this->is_active,
                media: $newFiles // Seulement les nouveaux fichiers
            );

            $this->product = $this->productService->updateProduct($this->product, $dto);

            Log::info('🎉 Product updated successfully', [
                'product_id' => $this->product->id,
                'new_files_added' => count($newFiles),
                'medias_deleted' => count($mediasToDelete),
                'final_media_count' => $this->product->fresh()->getMedia('medias')->count(),
            ]);

            session()->flash('success', 'Produit mis à jour avec succès !');
            $this->redirectRoute('customer.products.index');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validation error', [
                'product_id' => $this->product->id,
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('❌ Error updating product', [
                'product_id' => $this->product->id,
                'message' => $e->getMessage(),
            ]);
            session()->flash('error', 'Erreur lors de la mise à jour du produit.');
        }
    }

    protected function customRequest(): FormRequest
    {
        return new UpdateProductRequest;
    }
}
