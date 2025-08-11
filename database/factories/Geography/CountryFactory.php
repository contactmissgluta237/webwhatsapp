<?php

namespace Database\Factories\Geography;

use App\Models\Geography\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->country,
            'code' => $this->faker->unique()->countryCode,
            'is_active' => true,
        ];
    }
}
