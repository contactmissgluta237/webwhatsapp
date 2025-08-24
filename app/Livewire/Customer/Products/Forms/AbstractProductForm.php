<?php

declare(strict_types=1);

namespace App\Livewire\Customer\Products\Forms;

use App\Services\Customer\ProductService;
use App\Traits\HasMediaFiles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

abstract class AbstractProductForm extends Component
{
    use HasMediaFiles;
    use WithFileUploads;

    public string $title = '';
    public string $description = '';
    public float $price = 0.0;
    public bool $is_active = true;

    public array $mediaFiles = [];
    public array $allMediaFiles = []; // Pour stocker tous les fichiers accumulés
    public int $maxFiles = 5;
    public int $maxFileSize = 10240;

    public array $allFiles = [];
    public array $newFiles = [];
    public array $existingFiles = [];

    protected ProductService $productService;

    public function boot(ProductService $productService): void
    {
        $this->productService = $productService;
    }

    public function rules(): array
    {
        // @phpstan-ignore-next-line
        return $this->customRequest()->rules();
    }

    public function messages(): array
    {
        return $this->customRequest()->messages();
    }

    public function updated($propertyName): void
    {
        if (! in_array($propertyName, ['allFiles', 'newFiles', 'existingFiles'])) {
            $this->validateOnly($propertyName);
        }
    }

    public function updatedMediaFiles(): void
    {
        if (! empty($this->mediaFiles)) {
            $this->validateOnly('mediaFiles.*');

            // Ajouter les nouveaux fichiers aux existants
            foreach ($this->mediaFiles as $file) {
                $this->allMediaFiles[] = $file;
            }

            Log::info('📁 Media files accumulated', [
                'new_files' => count($this->mediaFiles),
                'total_accumulated' => count($this->allMediaFiles),
            ]);

            // Vérifier la limite
            if (count($this->allMediaFiles) > $this->maxFiles) {
                $this->addError('mediaFiles', "Maximum {$this->maxFiles} fichiers autorisés.");
                $this->allMediaFiles = array_slice($this->allMediaFiles, 0, $this->maxFiles);
            }

            // Reset pour permettre nouvelle sélection
            $this->reset('mediaFiles');
        }
    }

    public function removeMediaFile(int $index): void
    {
        if (isset($this->allMediaFiles[$index])) {
            array_splice($this->allMediaFiles, $index, 1);
            Log::info('🗑️ Media file removed', ['index' => $index, 'remaining' => count($this->allMediaFiles)]);
        }
    }

    public function clearAllMediaFiles(): void
    {
        $count = count($this->allMediaFiles);
        $this->allMediaFiles = [];
        Log::info('🗑️ All media files cleared', ['removed_count' => $count]);
    }

    public function hasMediaFiles(): bool
    {
        return count($this->allMediaFiles) > 0;
    }

    public function getMediaFilesCount(): int
    {
        return count($this->allMediaFiles);
    }

    protected function getMediasForSave(): array
    {
        return $this->allMediaFiles;
    }

    protected function hasNewFiles(): bool
    {
        return ! empty($this->newFiles);
    }

    protected function hasExistingFiles(): bool
    {
        return ! empty($this->existingFiles);
    }

    protected function getTotalFilesCount(): int
    {
        return count($this->allFiles);
    }

    abstract protected function customRequest(): FormRequest;

    abstract public function save(): void;

    abstract protected function getInitialFiles(): array;

    public function render()
    {
        return view('livewire.customer.products.forms.product-form');
    }
}
