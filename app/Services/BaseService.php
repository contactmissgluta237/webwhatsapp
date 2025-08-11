<?php

namespace App\Services;

use App\DTOs\ModelWithImagesDTO;
use App\Services\Shared\Media\MediaServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
        $strategy = $this->getMediaStrategy();
        match ($strategy) {
            'main_only' => $this->mediaService->handleMainImageStrategy(
                $model,
                $this->extractMainImage($data)
            ),
            'multiple_only' => $this->mediaService->handleMultipleImagesOnlyStrategy(
                $model,
                $this->extractImages($data)
            ),
            'single' => $this->mediaService->handleSingleImageStrategy(
                $model,
                $this->extractSingleImageFromData($data)
            ),
            'main_with_multiple' => $this->mediaService->handleMainWithMultipleStrategy(
                $model,
                $this->extractMainImage($data),
                $this->extractImages($data)
            ),
            default => null
        };
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
}
