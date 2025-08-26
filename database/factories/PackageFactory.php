<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    protected $model = Package::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'name' => $name,
            'display_name' => ucwords($name),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(100, 5000),
            'currency' => 'XAF',
            'messages_limit' => $this->faker->numberBetween(50, 1000),
            'context_limit' => $this->faker->numberBetween(10, 50),
            'accounts_limit' => $this->faker->numberBetween(1, 10),
            'products_limit' => $this->faker->numberBetween(5, 100),
            'duration_days' => $this->faker->numberBetween(30, 365),
            'is_recurring' => true,
            'one_time_only' => false,
            'features' => [],
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
