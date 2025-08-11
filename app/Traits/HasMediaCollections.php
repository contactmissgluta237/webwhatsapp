<?php

namespace App\Traits;

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
            } elseif ($collectionName === 'images') {
                $this->addMediaCollection('images')
                    ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
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

        $this->registerConversionForCollections('thumb', 150, 150, $collections);
        $this->registerConversionForCollections('medium', 500, 500, $collections);
        $this->registerConversionForCollections('large', 1200, 1200, $collections);
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
        return ['images'];
    }

    abstract public function requiresMainImage(): bool;

    abstract public function supportsMultipleImages(): bool;

    abstract public function getImageIdentifier(): string;
}
