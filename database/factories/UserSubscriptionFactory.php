<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSubscription>
 */
class UserSubscriptionFactory extends Factory
{
    protected $model = UserSubscription::class;

    public function definition(): array
    {
        $startsAt = $this->faker->dateTimeBetween('-1 month', 'now');

        return [
            'user_id' => User::factory(),
            'package_id' => Package::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $this->faker->dateTimeBetween($startsAt, '+2 months'),
            'status' => 'active',
            'messages_limit' => $this->faker->numberBetween(50, 1000),
            'context_limit' => $this->faker->numberBetween(10, 50),
            'accounts_limit' => $this->faker->numberBetween(1, 10),
            'products_limit' => $this->faker->numberBetween(5, 100),
        ];
    }
}
