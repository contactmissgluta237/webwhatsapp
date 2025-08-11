<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function definition(): array
    {
        $productType = $this->faker->randomElement([ProductType::BOTTLE(), ProductType::ACCESSORY()]);

        if ($productType->equals(ProductType::BOTTLE())) {
            $productTypeInstance = \Database\Factories\BottleTypeFactory::new()->create();
        } else {
            $productTypeInstance = \Database\Factories\AccessoryTypeFactory::new()->create();
        }

        return [
            'product_type' => $productType,
            'product_type_id' => $productTypeInstance->id,
        ];
    }

    public function bottleType(): Factory
    {
        return $this->state(function (array $attributes) {
            $bottleType = \Database\Factories\BottleTypeFactory::new()->create();

            return [
                'product_type' => ProductType::BOTTLE(),
                'product_type_id' => $bottleType->id,
            ];
        });
    }

    public function accessoryType(): Factory
    {
        return $this->state(function (array $attributes) {
            $accessoryType = \Database\Factories\AccessoryTypeFactory::new()->create();

            return [
                'product_type' => ProductType::ACCESSORY(),
                'product_type_id' => $accessoryType->id,
            ];
        });
    }
}
