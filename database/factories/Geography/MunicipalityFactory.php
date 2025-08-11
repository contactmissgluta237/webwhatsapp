<?php

namespace Database\Factories\Geography;

use App\Models\Geography\Municipality;
use Illuminate\Database\Eloquent\Factories\Factory;

class MunicipalityFactory extends Factory
{
    protected $model = Municipality::class;

    public function definition(): array
    {
        return [
            'city_id' => \Database\Factories\Geography\CityFactory::new(),
            'name' => $this->faker->streetName,
            'is_active' => true,
        ];
    }
}
