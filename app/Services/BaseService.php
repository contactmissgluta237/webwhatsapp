<?php

namespace App\Services;

use App\DTOs\ModelWithImagesDTO;
use App\Services\Shared\Media\MediaServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\HasMedia;

abstract class BaseService implements HasMediaServiceInterface
{
    public function __construct(
        protected MediaServiceInterface $mediaService
    ) {}

    public function createWithMedia(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $modelData = $this->filterDataForModel($data);
            /** @var Model&HasMedia $model */
            $model = $this->getModel()::create($modelData);

            $this->processMediaWithStrategy($model, $data);

            return $model->load('media');
        });
    }

    /**
     * Update a model with media handling
     */
    public function updateWithMedia(HasMedia|Model $model, array $data, ?array $imagesIdsToDelete = null): Model
    {
        return DB::transaction(function () use ($model, $data, $imagesIdsToDelete) {
            $modelData = $this->filterDataForModel($data);
            /** @var Model&HasMedia $updatedModel */
            $updatedModel = tap($model)->update($modelData);

            if (! empty($imagesIdsToDelete)) {
                foreach ($imagesIdsToDelete as $mediaId) {
                    $this->mediaService->detachMedia($updatedModel, (int) $mediaId);
                }
            }

            $this->processMediaWithStrategy($updatedModel, $data);

            return $updatedModel->load('media');
        });
    }

    /**
     * Remove a media item from a model
     */
    public function removeMedia(HasMedia $model, int $mediaId): bool
    {
        return $this->mediaService->detachMedia($model, $mediaId);
    }

    public function getWithMediaData(int $id): ?ModelWithImagesDTO
    {
        /** @var (Model&HasMedia)|null $model */
        $model = Model::find($id);

        if (! $model) {
            return null;
        }

        return $this->mediaService->getModelMediaData($model);
    }

    protected function processMediaWithStrategy(HasMedia $model, array $data): void
    {
        Log::info('🔍 BaseService::processMediaWithStrategy() START', [
            'model_id' => $model->id ?? 'new',
            'data_keys' => array_keys($data),
            'has_medias' => isset($data['medias']),
            'medias_count' => isset($data['medias']) ? (is_array($data['medias']) ? count($data['medias']) : 'not-array') : 'not-set',
            'medias_types' => isset($data['medias']) && is_array($data['medias']) ? array_map('gettype', $data['medias']) : 'not-available',
        ]);

        $strategy = $this->getMediaStrategy();
        $collectionName = $this->getMediaCollectionName();

        Log::info('🔍 BaseService::processMediaWithStrategy() - Strategy determined', [
            'strategy' => $strategy,
            'collection_name' => $collectionName,
        ]);

        match ($strategy) {
            'main_only' => $this->mediaService->handleMainImageStrategy(
                $model,
                $this->extractMainImage($data)
            ),
            'multiple_only' => $this->processMultipleOnlyStrategy($model, $data, $collectionName),
            'single' => $this->mediaService->handleSingleImageStrategy(
                $model,
                $this->extractSingleImageFromData($data),
                $collectionName
            ),
            'main_with_multiple' => $this->mediaService->handleMainWithMultipleStrategy(
                $model,
                $this->extractMainImage($data),
                $this->extractImages($data),
                $collectionName
            ),
            default => Log::warning('🔍 BaseService::processMediaWithStrategy() - Unknown strategy', ['strategy' => $strategy])
        };
    }

    private function processMultipleOnlyStrategy(HasMedia $model, array $data, string $collectionName): void
    {
        $images = $this->extractImages($data);

        Log::info('🔍 BaseService::processMultipleOnlyStrategy()', [
            'images_count' => count($images),
            'images_types' => array_map('gettype', $images),
            'first_image_class' => ! empty($images) ? (is_object($images[0]) ? get_class($images[0]) : gettype($images[0])) : 'no-images',
        ]);

        $this->mediaService->handleMultipleImagesOnlyStrategy($model, $images, $collectionName);
    }

    private function extractSingleImageFromData(array $data): ?UploadedFile
    {
        foreach ($this->getMediaFields() as $field) {
            if (isset($data[$field]) && $data[$field] instanceof UploadedFile) {
                return $data[$field];
            }
        }

        return null;
    }

    private function extractMainImage(array $data): ?UploadedFile
    {
        return isset($data['main_image']) && $data['main_image'] instanceof UploadedFile
            ? $data['main_image']
            : null;
    }

    private function extractImages(array $data): array
    {
        if (isset($data['medias']) && is_array($data['medias'])) {
            return $data['medias'];
        }

        return isset($data['images']) && is_array($data['images'])
            ? $data['images']
            : [];
    }

    protected function filterDataForModel(array $data): array
    {
        return collect($data)
            ->except($this->getMediaFields())
            ->toArray();
    }

    protected function getMediaStrategy(): string
    {
        $modelClass = $this->getModel();
        $model = new $modelClass;

        if ($model->requiresMainImage() && $model->supportsMultipleImages()) {
            return 'main_with_multiple';
        }

        if ($model->requiresMainImage() && ! $model->supportsMultipleImages()) {
            return 'main_only';
        }

        if (! $model->requiresMainImage() && $model->supportsMultipleImages()) {
            return 'multiple_only';
        }

        return 'single';
    }

    /**
     * Should return just image fields to not consider when filtering data for model
     *
     * @return string[]
     */
    abstract protected function getMediaFields(): array;

    /**
     * Get the model class name
     *
     * @return class-string
     */
    abstract protected function getModel(): string;

    abstract protected function getMediaCollectionName(): string;
}
