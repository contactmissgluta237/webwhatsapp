<?php

namespace Database\Factories;

use App\Models\DeliveryPerson;
use App\Models\DistributionCenter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryPersonFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DeliveryPerson::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->deliveryPerson(),
        ];
    }

    public function assignedToCenter(int $centerId, bool $isActive = true): static
    {
        return $this->afterCreating(function (DeliveryPerson $deliveryPerson) use ($centerId, $isActive) {
            $deliveryPerson->distributionCenters()->attach($centerId, [
                'is_active' => $isActive,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function assignedToRandomCenters(int $count = 1, bool $isActive = true): static
    {
        return $this->afterCreating(function (DeliveryPerson $deliveryPerson) use ($count, $isActive) {
            $centers = DistributionCenter::inRandomOrder()->take($count)->pluck('id');

            foreach ($centers as $centerId) {
                $deliveryPerson->distributionCenters()->attach($centerId, [
                    'is_active' => $isActive,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function inactive(): static
    {
        return $this->assignedToRandomCenters(1, false);
    }
}
