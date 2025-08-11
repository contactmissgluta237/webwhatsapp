<?php

namespace Database\Factories;

use App\Enums\ExternalTransactionType;
use App\Models\ExternalTransaction;
use App\Models\PaymentProviderBalance;
use App\Models\PaymentProviderTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentProviderTransaction>
 */
class PaymentProviderTransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentProviderTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_provider_balance_id' => PaymentProviderBalance::factory(),
            'external_transaction_id' => ExternalTransaction::factory(),
            'type' => $this->faker->randomElement(ExternalTransactionType::values()),
            'amount' => $this->faker->randomFloat(2, 100, 100000),
            'description' => $this->faker->sentence(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate a specific type for the transaction.
     */
    public function type(ExternalTransactionType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Indicate that the transaction is linked to a specific external transaction.
     */
    public function forExternalTransaction(ExternalTransaction $externalTransaction): static
    {
        return $this->state(fn (array $attributes) => [
            'external_transaction_id' => $externalTransaction->id,
        ]);
    }

    /**
     * Indicate that the transaction is created by a specific user.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
