<?php

declare(strict_types=1);

namespace App\Services\Customer;

use App\DTOs\Customer\CreateProductDTO;
use App\DTOs\Customer\UpdateProductDTO;
use App\Models\UserProduct;
use App\Services\BaseService;
use App\Services\Shared\Media\MediaServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class ProductService extends BaseService
{
    public function __construct(MediaServiceInterface $mediaService)
    {
        parent::__construct($mediaService);
    }

    public function createProduct(CreateProductDTO $dto): UserProduct
    {
        Log::info('🏭 ProductService::createProduct() START', [
            'dto_data' => $dto->toArray(),
            'user_id' => $dto->user_id ?? Auth::id(),
        ]);

        $data = $dto->toArray();
        $data['user_id'] = $dto->user_id ?? Auth::id();

        // Transform media field to medias for BaseService compatibility
        if (isset($data['media'])) {
            Log::info('🔄 ProductService::createProduct() - Transforming media to medias', [
                'media_count' => is_array($data['media']) ? count($data['media']) : 'not-array',
                'media_preview' => is_array($data['media']) ? array_slice($data['media'], 0, 2) : $data['media'],
            ]);
            $data['medias'] = $data['media'];
            unset($data['media']);
        }

        Log::info('🔍 ProductService::createProduct() - Calling createWithMedia', [
            'final_data_keys' => array_keys($data),
            'medias_count' => isset($data['medias']) ? (is_array($data['medias']) ? count($data['medias']) : 'not-array') : 'not-set',
        ]);

        /** @var UserProduct */
        $product = $this->createWithMedia($data);

        Log::info('🎉 ProductService::createProduct() - Product created', [
            'product_id' => $product->id,
            'media_count' => $product->getMedia('medias')->count(),
        ]);

        return $product;
    }

    public function updateProduct(UserProduct $product, UpdateProductDTO $dto, ?array $mediaIdsToDelete = null): UserProduct
    {
        $data = $dto->toArray();

        // Transform media field to medias for BaseService compatibility
        if (isset($data['media'])) {
            $data['medias'] = $data['media'];
            unset($data['media']);
        }

        /** @var UserProduct */
        $product = $this->updateWithMedia($product, $data, $mediaIdsToDelete);

        return $product;
    }

    public function deleteProduct(UserProduct $product): bool
    {
        $product->clearMediaCollection('medias');

        return $product->delete();
    }

    protected function getMediaFields(): array
    {
        return ['medias'];
    }

    protected function getModel(): string
    {
        return UserProduct::class;
    }

    protected function getMediaCollectionName(): string
    {
        return 'medias';
    }

    protected function filterDataForModel(array $data): array
    {
        return collect($data)
            ->except(['medias'])
            ->toArray();
    }
}
