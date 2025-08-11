<?php

declare(strict_types=1);

namespace App\Services\Shared\Media;

use App\DTOs\ImageDataDTO;
use App\DTOs\ModelWithImagesDTO;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;

final class SpatieMediaService implements MediaServiceInterface
{
    public function attachMedia(HasMedia $model, UploadedFile|array $files, string $collection = 'images'): array
    {
        $validFiles = is_array($files) ? $files : [$files];

        if (empty($validFiles)) {
            return [];
        }

        return array_filter(array_map(
            fn (UploadedFile $file) => $this->addSingleMedia($model, $file, $collection),
            $validFiles
        ));
    }

    public function detachMedia(HasMedia $model, int $mediaId): bool
    {
        $media = $model->getMedia('images')->firstWhere('id', $mediaId);

        return $media?->delete() ?? false;
    }

    public function replaceMedia(HasMedia $model, UploadedFile|array $files, string $collection = 'images'): array
    {
        $this->clearMediaCollection($model, $collection);

        return $this->attachMedia($model, $files, $collection);
    }

    public function getModelMediaData(HasMedia $model): ?ModelWithImagesDTO
    {
        if ($model instanceof Model) {
            $model->load('media');
        }

        return ModelWithImagesDTO::fromModel($model);
    }

    public function getAllImagesForModel(HasMedia $model): array
    {
        if ($model instanceof Model) {
            $model->load('media');
        }

        return $model->getMedia('images')
            ->map(fn ($media) => ImageDataDTO::fromMedia($media))
            ->toArray();
    }

    public function clearMediaCollection(HasMedia $model, string $collection): bool
    {
        try {
            $model->clearMediaCollection($collection);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function syncMedia(HasMedia $model, ?UploadedFile $file, string $collection = 'default'): void
    {
        if ($file) {
            $this->replaceMedia($model, $file, $collection);
        } else {
            $this->clearMediaCollection($model, $collection);
        }
    }

    public function handleMainWithMultipleStrategy(HasMedia $model, ?UploadedFile $mainImage, array $images): void
    {
        if ($mainImage) {
            $this->replaceMedia($model, $mainImage, 'main_image');
        }

        if (! empty($images)) {
            $this->attachMedia($model, $images, 'images');
        }
    }

    public function handleMainImageStrategy(HasMedia $model, UploadedFile $mainImage): void
    {
        $this->replaceMedia($model, $mainImage, 'main_image');
    }

    public function handleMultipleImagesOnlyStrategy(HasMedia $model, array $images): void
    {
        $this->attachMedia($model, $images, 'images');
    }

    public function handleSingleImageStrategy(HasMedia $model, UploadedFile $image): void
    {
        $this->replaceMedia($model, $image, 'images');
    }

    private function addSingleMedia(HasMedia $model, UploadedFile $file, string $collection): mixed
    {
        try {
            return $model->addMedia($file)->toMediaCollection($collection);
        } catch (\Exception) {
            return null;
        }
    }
}
