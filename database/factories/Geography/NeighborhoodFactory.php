<?php

namespace Database\Factories\Geography;

use App\Models\Geography\Neighborhood;
use Illuminate\Database\Eloquent\Factories\Factory;

class NeighborhoodFactory extends Factory
{
    protected $model = Neighborhood::class;

    public function definition(): array
    {
        return [
            'municipality_id' => \Database\Factories\Geography\MunicipalityFactory::new(),
            'name' => $this->faker->streetAddress,
            'is_active' => true,
        ];
    }
}
