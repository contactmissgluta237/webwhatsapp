<?php

namespace App\Traits;

use App\Enums\MediaConversionSize;
use Exception;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasMediaCollections
{
    use InteractsWithMedia;

    /**
     * We override this method from spatie/laravel-medialibrary
     * to ensure that we only register the media collections
     * that are relevant to this model.
     */
    public function registerMediaCollections(): void
    {
        $collections = $this->getImageCollections();

        foreach ($collections as $collectionName) {
            if ($collectionName === 'main_image') {
                $this->addMediaCollection('main_image')
                    ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
            } elseif ($collectionName === 'medias') {
                $this->addMediaCollection('medias')
                    ->acceptsMimeTypes([
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                        'image/gif',
                        'image/svg+xml',
                        'video/mp4',
                        'video/avi',
                        'video/quicktime',
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/x-empty', // For fake test files
                    ]);
            } elseif ($collectionName === 'avatar') {
                $this->addMediaCollection('avatar')
                    ->singleFile()
                    ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
            }
        }
    }

    /**
     * Register media conversions for the model.
     * This method is called when the model is initialized.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $collections = $this->getImageCollections();

        if (empty($collections)) {
            return;
        }

        $this->registerConversionForCollections('thumb', MediaConversionSize::THUMBNAIL()->width(), MediaConversionSize::THUMBNAIL()->height(), $collections);
        $this->registerConversionForCollections('medium', MediaConversionSize::MEDIUM()->width(), MediaConversionSize::MEDIUM()->height(), $collections);
        $this->registerConversionForCollections('large', MediaConversionSize::LARGE()->width(), MediaConversionSize::LARGE()->height(), $collections);
    }

    /**
     * @param  array<string>  $collections
     */
    private function registerConversionForCollections(string $name, int $width, int $height, array $collections): void
    {
        /** @phpstan-ignore-next-line */
        $this->addMediaConversion($name)
            ->width($width)
            ->height($height)
            ->optimize()
            ->withResponsiveImages()
            ->performOnCollections(...$collections);
    }

    public function setMainImage(UploadedFile $file, ?string $name = null): ?Media
    {
        if (! $this->requiresMainImage()) {
            throw new Exception('This model does not support main images');
        }

        return $this->addMedia($file)
            ->usingName($name ?? 'Main Image')
            ->toMediaCollection('main_image');
    }

    public function getImageCollections(): array
    {
        return ['medias'];
    }

    abstract public function requiresMainImage(): bool;

    abstract public function supportsMultipleImages(): bool;

    abstract public function getImageIdentifier(): string;
}
