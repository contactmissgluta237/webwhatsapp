<?php

namespace App\Services;

use App\DTOs\ModelWithImagesDTO;
use Illuminate\Database\Eloquent\Model;

interface HasMediaServiceInterface
{
    public function createWithMedia(array $data): Model;

    public function updateWithMedia(Model $model, array $data, ?array $imagesIdsToDelete = null): Model;

    public function getWithMediaData(int $id): ?ModelWithImagesDTO;
}
