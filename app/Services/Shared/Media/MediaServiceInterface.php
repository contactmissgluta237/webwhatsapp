<?php

namespace App\Services\Shared\Media;

use App\DTOs\ImageDataDTO;
use App\DTOs\ModelWithImagesDTO;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface MediaServiceInterface
{
    /**
     * Attach media to a model
     *
     * @param  UploadedFile|UploadedFile[]  $files
     * @return array<Media>
     */
    public function attachMedia(HasMedia $model, UploadedFile|array $files, string $collection = 'images'): array;

    /**
     * Detach media from a model
     */
    public function detachMedia(HasMedia $model, int $mediaId): bool;

    /**
     * Replace all media in a collection
     *
     * @param  UploadedFile|UploadedFile[]  $files
     * @return array<Media>
     */
    public function replaceMedia(HasMedia $model, UploadedFile|array $files, string $collection = 'images'): array;

    /**
     * Get media data for a model
     */
    public function getModelMediaData(HasMedia $model): ?ModelWithImagesDTO;

    /**
     * Get all images for a model
     *
     * @return array<ImageDataDTO>
     */
    public function getAllImagesForModel(HasMedia $model): array;

    /**
     * Clear a media collection for a model
     */
    public function clearMediaCollection(HasMedia $model, string $collection): bool;

    /**
     * Handle media strategy for models with main image and multiple images
     *
     * @param  UploadedFile[]  $images
     */
    public function handleMainWithMultipleStrategy(HasMedia $model, ?UploadedFile $mainImage, array $images): void;

    /**
     * Handle media strategy for models with main image only
     */
    public function handleMainImageStrategy(HasMedia $model, UploadedFile $mainImage): void;

    /**
     * Handle media strategy for models with multiple images only
     *
     * @param  UploadedFile[]  $images
     */
    public function handleMultipleImagesOnlyStrategy(HasMedia $model, array $images): void;

    /**
     * Handle media strategy for models with a single image
     */
    public function handleSingleImageStrategy(HasMedia $model, UploadedFile $image): void;

    /**
     * Synchronize media for a model.
     *
     * @param  UploadedFile|null  $file  The file to sync. Null to clear the collection.
     */
    public function syncMedia(HasMedia $model, ?UploadedFile $file, string $collection = 'default'): void;
}
