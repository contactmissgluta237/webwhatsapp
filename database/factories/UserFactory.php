<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower($firstName.'.'.$lastName.'@example.com'),
            'phone_number' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'), // Default password for test users
            'remember_token' => Str::random(10),
            'is_active' => true,
            'affiliation_code' => null, // Will be generated automatically by User model boot method
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): self
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create an admin user.
     */
    public function admin(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'email' => 'admin_'.Str::random(5).'@example.com',
            ];
        })->afterCreating(function (User $user) {
            $user->assignRole('admin');
        });
    }

    /**
     * Create a customer user.
     */
    public function customer(?int $referrerId = null): static
    {
        return $this->state(function (array $attributes) use ($referrerId) {
            return [
                'email' => 'customer_'.Str::random(5).'@example.com',
                'referrer_id' => $referrerId,
            ];
        })->afterCreating(function (User $user) {
            $user->assignRole('customer');

            Customer::create([
                'user_id' => $user->id,
            ]);
        });
    }

    /**
     * Create a customer user with a specific referrer.
     */
    public function customerWithReferrer(int $referrerId): static
    {
        return $this->customer($referrerId);
    }
}
