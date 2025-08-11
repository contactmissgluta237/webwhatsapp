<?php

namespace Database\Factories;

use App\Models\DistributionCenter;
use App\Models\Geography\Neighborhood;
use Illuminate\Database\Eloquent\Factories\Factory;

class DistributionCenterFactory extends Factory
{
    protected $model = DistributionCenter::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'neighborhood_id' => Neighborhood::factory(),
            'address' => $this->faker->address,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->unique()->safeEmail,
            'is_active' => true,
        ];
    }
}
