<?php

namespace App\DTOs;

class ImageDataDTO extends BaseDTO
{
    public function __construct(
        public readonly ?string $url,
        public readonly ?string $thumb,
        public readonly ?string $medium,
        public readonly ?string $large,
        public readonly ?int $id = null,
        public readonly ?string $name = null
    ) {}

    public static function fromMedia($media): self
    {
        if (! $media) {
            return new self(null, null, null, null);
        }

        return new self(
            url: $media->getUrl(),
            thumb: $media->getUrl('thumb'),
            medium: $media->getUrl('medium'),
            large: $media->getUrl('large'),
            id: $media->id,
            name: $media->name
        );
    }
}
