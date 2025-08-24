<?php

declare(strict_types=1);

namespace App\Services\Shared\Media;

use App\DTOs\ImageDataDTO;
use App\DTOs\ModelWithImagesDTO;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\HasMedia;

final class SpatieMediaService implements MediaServiceInterface
{
    public function attachMedia(HasMedia $model, UploadedFile|array $files, string $collection = 'images'): array
    {
        Log::info('🔍 SpatieMediaService::attachMedia() START', [
            'model_id' => $model->id ?? 'new',
            'collection' => $collection,
            'files_type' => gettype($files),
            'files_count' => is_array($files) ? count($files) : 1,
            'files_types' => is_array($files) ? array_map('gettype', $files) : [gettype($files)],
        ]);

        $validFiles = is_array($files) ? $files : [$files];

        if (empty($validFiles)) {
            Log::warning('🔍 SpatieMediaService::attachMedia() - No files provided');

            return [];
        }

        Log::info('🔍 SpatieMediaService::attachMedia() - Processing files', [
            'valid_files_count' => count($validFiles),
            'detailed_files' => array_map(function ($file, $index) {
                return [
                    'index' => $index,
                    'type' => gettype($file),
                    'class' => is_object($file) ? get_class($file) : 'not-object',
                    'is_uploaded_file' => $file instanceof UploadedFile,
                ];
            }, $validFiles, array_keys($validFiles)),
        ]);

        $result = array_filter(array_map(
            fn (UploadedFile $file) => $this->addSingleMedia($model, $file, $collection),
            $validFiles
        ));

        Log::info('🔍 SpatieMediaService::attachMedia() - Result', [
            'result_count' => count($result),
            'successful_uploads' => count(array_filter($result)),
        ]);

        return $result;
    }

    public function detachMedia(HasMedia $model, int $mediaId): bool
    {
        $media = $model->getMedia('medias')->firstWhere('id', $mediaId);

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

        return $model->getMedia('medias')
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

    public function handleMainWithMultipleStrategy(HasMedia $model, ?UploadedFile $mainImage, array $images, string $collectionName = 'images'): void
    {
        if ($mainImage) {
            $this->replaceMedia($model, $mainImage, 'main_image');
        }

        if (! empty($images)) {
            $this->attachMedia($model, $images, $collectionName);
        }
    }

    public function handleMainImageStrategy(HasMedia $model, UploadedFile $mainImage): void
    {
        $this->replaceMedia($model, $mainImage, 'main_image');
    }

    public function handleMultipleImagesOnlyStrategy(HasMedia $model, array $images, string $collectionName = 'images'): void
    {
        $this->attachMedia($model, $images, $collectionName);
    }

    public function handleSingleImageStrategy(HasMedia $model, UploadedFile $image, string $collectionName = 'images'): void
    {
        $this->replaceMedia($model, $image, $collectionName);
    }

    private function addSingleMedia(HasMedia $model, UploadedFile $file, string $collection): mixed
    {
        Log::info('🔍 SpatieMediaService::addSingleMedia() START', [
            'model_id' => $model->id ?? 'new',
            'collection' => $collection,
            'file_type' => gettype($file),
            'file_class' => is_object($file) ? get_class($file) : 'not-object',
            'is_uploaded_file' => $file instanceof UploadedFile,
            'file_name' => $file instanceof UploadedFile ? $file->getClientOriginalName() : 'not-uploaded-file',
            'file_size' => $file instanceof UploadedFile ? $file->getSize() : 'not-uploaded-file',
        ]);

        try {
            if (! ($file instanceof UploadedFile)) {
                Log::error('🔍 SpatieMediaService::addSingleMedia() - File is not UploadedFile', [
                    'file_type' => gettype($file),
                    'file_content' => $file,
                ]);

                return null;
            }

            $result = $model->addMedia($file)->toMediaCollection($collection);

            Log::info('🔍 SpatieMediaService::addSingleMedia() - Success', [
                'media_id' => $result->id ?? 'unknown',
                'file_name' => $file->getClientOriginalName(),
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('🔍 SpatieMediaService::addSingleMedia() - Exception', [
                'error' => $e->getMessage(),
                'file_name' => $file instanceof UploadedFile ? $file->getClientOriginalName() : 'not-uploaded-file',
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }
}
