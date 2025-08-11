<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Geography\Neighborhood;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerDeliveryAddress>
 */
class CustomerDeliveryAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'neighborhood_id' => Neighborhood::factory(),
            'label' => fake()->randomElement(['Domicile', 'Bureau', 'Entrepôt', 'Magasin']),
            'address' => fake()->address(),
            'is_default' => false,
        ];
    }

    /**
     * Mark this address as default.
     */
    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
