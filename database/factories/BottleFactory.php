<?php

namespace Database\Factories;

use App\Enums\BottleStatus;
use App\Models\BottleType;
use App\Models\DistributionCenter;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bottle>
 */
class BottleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $bottleType = BottleType::inRandomOrder()->first();
        $product = Product::inRandomOrder()->first();
        $distributionCenter = DistributionCenter::inRandomOrder()->first();

        if (! $bottleType) {
            throw new \RuntimeException('No bottle types found. Run BottleTypeSeeder first.');
        }

        if (! $product) {
            throw new \RuntimeException('No products found. Run ProductSeeder first.');
        }

        if (! $distributionCenter) {
            throw new \RuntimeException('No distribution centers found. Run DistributionCenterSeeder first.');
        }

        return [
            'product_id' => $product->id,
            'distribution_center_id' => $distributionCenter->id,
            'barcode' => fake()->unique()->ean13(),
            'is_filled' => fake()->boolean(80), // 80% chance of being filled
            'status' => fake()->randomElement(BottleStatus::values()),
        ];
    }

    /**
     * Configure bottle as in stock.
     */
    public function inStock(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BottleStatus::IN_STOCK(),
            ];
        });
    }

    /**
     * Configure bottle as with a delivery person.
     */
    public function withDeliveryPerson(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BottleStatus::WITH_DELIVERY_PERSON(),
            ];
        });
    }

    /**
     * Configure bottle as with a client.
     */
    public function withClient(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BottleStatus::WITH_CLIENT(),
            ];
        });
    }
}
