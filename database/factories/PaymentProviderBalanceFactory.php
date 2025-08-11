<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\PaymentProviderBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentProviderBalance>
 */
class PaymentProviderBalanceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentProviderBalance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => $this->faker->unique()->randomElement(PaymentMethod::values()),
            'balance' => $this->faker->randomFloat(2, 0, 1000000),
            'is_active' => $this->faker->boolean(),
        ];
    }

    /**
     * Indicate that the balance is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate a specific type for the balance.
     */
    public function type(PaymentMethod $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}
