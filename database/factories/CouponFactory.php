<?php

namespace Database\Factories;

use App\Enums\CouponStatus;
use App\Enums\CouponType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('????')),
            'type' => $this->faker->randomElement([CouponType::PERCENTAGE(), CouponType::FIXED_AMOUNT()]),
            'value' => $this->faker->randomFloat(2, 5, 50),
            'status' => CouponStatus::ACTIVE(),
            'is_active' => true,
            'usage_limit' => $this->faker->numberBetween(10, 100),
            'per_user_limit' => $this->faker->numberBetween(1, 5),
            'used_count' => 0,
            'valid_from' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'valid_until' => $this->faker->dateTimeBetween('now', '+1 month'),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the coupon is for percentage discount.
     */
    public function percentage(float $percentage = 20.0): static
    {
        return $this->state([
            'type' => CouponType::PERCENTAGE(),
            'value' => $percentage,
        ]);
    }

    /**
     * Indicate that the coupon is for fixed amount discount.
     */
    public function fixedAmount(float $amount = 500.0): static
    {
        return $this->state([
            'type' => CouponType::FIXED_AMOUNT(),
            'value' => $amount,
        ]);
    }

    /**
     * Indicate that the coupon is expired.
     */
    public function expired(): static
    {
        return $this->state([
            'valid_from' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
            'valid_until' => $this->faker->dateTimeBetween('-1 week', '-1 day'),
        ]);
    }

    /**
     * Indicate that the coupon is inactive.
     */
    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
            'status' => CouponStatus::EXPIRED(),
        ]);
    }
}
