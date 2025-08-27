<?php

namespace App\Services\Shared\Media;

use App\DTOs\ImageDataDTO;
use App\DTOs\ModelWithImagesDTO;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaService implements MediaServiceInterface
{
    public function attachMedia(HasMedia $model, UploadedFile|array $files, string $collection = 'images'): array
    {
        $mediaItems = [];
        $files = is_array($files) ? $files : [$files];

        foreach ($files as $file) {
            $processedFile = $this->processFile($file);
            if ($processedFile) {
                $mediaItems[] = $model->addMedia($processedFile)
                    ->usingName($processedFile->getClientOriginalName())
                    ->toMediaCollection($collection);
            }
        }

        return $mediaItems;
    }

    private function processFile(mixed $file): ?UploadedFile
    {
        // Si c'est déjà un UploadedFile standard, on le retourne tel quel
        if ($file instanceof UploadedFile && ! $file instanceof TemporaryUploadedFile) {
            return $file;
        }

        // Si c'est un TemporaryUploadedFile de Livewire
        if ($file instanceof TemporaryUploadedFile) {
            return $this->convertTemporaryUploadedFile($file);
        }

        return null;
    }

    private function convertTemporaryUploadedFile(TemporaryUploadedFile $temporaryFile): ?UploadedFile
    {
        $tempPath = $temporaryFile->getRealPath();

        if (! file_exists($tempPath)) {
            return null;
        }

        return new UploadedFile(
            $tempPath,
            $temporaryFile->getClientOriginalName(),
            $temporaryFile->getMimeType(),
            null,
            true // test parameter set to true for temporary files
        );
    }

    public function detachMedia(HasMedia $model, int $mediaId): bool
    {
        $media = $model->getMedia()->firstWhere('id', $mediaId);

        if ($media) {
            return $media->delete();
        }

        return false;
    }

    public function replaceMedia(HasMedia $model, UploadedFile|array $files, string $collection = 'images'): array
    {
        $model->clearMediaCollection($collection);

        return $this->attachMedia($model, $files, $collection);
    }

    public function getModelMediaData(HasMedia $model): ?ModelWithImagesDTO
    {
        // Implementation depends on how you want to structure this DTO
        return null;
    }

    public function getAllImagesForModel(HasMedia $model): array
    {
        return $model->getMedia('medias')->map(function (Media $media): ImageDataDTO {
            return new ImageDataDTO(
                url: $media->getUrl(),
                thumb: $media->getUrl('thumb'),
                medium: $media->getUrl('medium'),
                large: $media->getUrl('large'),
                id: $media->id,
                name: $media->file_name,
            );
        })->toArray();
    }

    public function clearMediaCollection(HasMedia $model, string $collection): bool
    {
        $model->clearMediaCollection($collection);

        return true;
    }

    public function handleMainWithMultipleStrategy(HasMedia $model, ?UploadedFile $mainImage, array $images, string $collectionName = 'images'): void
    {
        if ($mainImage) {
            $this->syncMedia($model, $mainImage, 'main_image');
        }

        if (! empty($images)) {
            $this->attachMedia($model, $images, $collectionName);
        }
    }

    public function handleMainImageStrategy(HasMedia $model, UploadedFile $mainImage): void
    {
        // Implementation for main image strategy
    }

    public function handleMultipleImagesOnlyStrategy(HasMedia $model, array $images, string $collectionName = 'images'): void
    {
        if (! empty($images)) {
            $this->attachMedia($model, $images, $collectionName);
        }
    }

    public function handleSingleImageStrategy(HasMedia $model, UploadedFile $image, string $collectionName = 'images'): void
    {
        $this->syncMedia($model, $image, $collectionName);
    }

    public function syncMedia(HasMedia $model, ?UploadedFile $file, string $collection = 'default'): void
    {
        if ($file) {
            $model->clearMediaCollection($collection);
            $model->addMedia($file)
                ->usingName($file->getClientOriginalName())
                ->toMediaCollection($collection);
        } else {
            $model->clearMediaCollection($collection);
        }
    }
}
