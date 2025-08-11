<?php

namespace Database\Factories;

use App\Models\AccessoryType;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccessoryTypeFactory extends Factory
{
    protected $model = AccessoryType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'description' => $this->faker->sentence,
            'is_active' => $this->faker->boolean,
        ];
    }
}
