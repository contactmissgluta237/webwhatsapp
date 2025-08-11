<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use App\Models\ProductCategoryCityPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductCategoryCityPriceFactory extends Factory
{
    protected $model = ProductCategoryCityPrice::class;

    public function definition(): array
    {
        return [
            'product_category_id' => ProductCategory::factory(),
            'city_id' => \App\Models\Geography\City::factory(),
            'content_price' => $this->faker->randomFloat(2, 100, 1000),
            'content_with_bottle_price' => $this->faker->randomFloat(2, 100, 1000),
        ];
    }
}
