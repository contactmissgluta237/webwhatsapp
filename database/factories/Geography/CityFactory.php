<?php

namespace Database\Factories\Geography;

use App\Models\Geography\City;
use Illuminate\Database\Eloquent\Factories\Factory;

class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        return [
            'country_id' => \Database\Factories\Geography\CountryFactory::new(),
            'name' => $this->faker->city,
            'is_active' => true,
        ];
    }
}
