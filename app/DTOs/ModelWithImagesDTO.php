<?php

namespace App\DTOs;

use Spatie\MediaLibrary\HasMedia;

class ModelWithImagesDTO
{
    public function __construct(
        public readonly HasMedia $model,
        public readonly ImageDataDTO $mainImage,
        /**
         * @var ImageDataDTO[]
         */
        public readonly array $images,
    ) {}

    public static function fromModel(HasMedia $model): self
    {
        $mainImage = ImageDataDTO::fromMedia($model->main_image ?? null);

        $images = $model->getMedia('medias')->map(fn ($media) => ImageDataDTO::fromMedia($media))->toArray();

        return new self($model, $mainImage, $images);
    }

    public function toArray(): array
    {
        return [
            'model' => $this->model->toArray(),
            'main_image' => $this->mainImage->toArray(),
            'images' => array_map(fn (ImageDataDTO $dto) => $dto->toArray(), $this->images),
        ];
    }
}
