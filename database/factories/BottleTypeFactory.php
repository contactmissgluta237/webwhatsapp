<?php

namespace Database\Factories;

use App\Models\BottleType;
use Illuminate\Database\Eloquent\Factories\Factory;

class BottleTypeFactory extends Factory
{
    protected $model = BottleType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'capacity' => $this->faker->randomFloat(2, 1, 50),
            'height' => $this->faker->randomFloat(2, 10, 100),
            'weight' => $this->faker->randomFloat(2, 1, 20),
            'radius' => $this->faker->randomFloat(2, 5, 30),
            'content_price' => $this->faker->randomFloat(2, 100, 1000),
            'bottle_with_content_price' => $this->faker->randomFloat(2, 100, 1000),
            'is_active' => $this->faker->boolean,
        ];
    }
}
