<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InternalTransaction>
 */
class InternalTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $transactionType = $this->faker->randomElement(TransactionType::values());
        $amount = $this->faker->numberBetween(1000, 50000);

        return [
            'wallet_id' => Wallet::factory(),
            'amount' => $amount,
            'transaction_type' => $transactionType,
            'status' => $this->faker->randomElement(TransactionStatus::values()),
            'description' => $this->faker->optional()->sentence(),
            'related_type' => null,
            'related_id' => null,
            'recipient_user_id' => null,
            'created_by' => User::factory(),
            'completed_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the transaction is a credit.
     */
    public function credit(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => TransactionType::CREDIT(),
        ]);
    }

    /**
     * Indicate that the transaction is a debit.
     */
    public function debit(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => TransactionType::DEBIT(),
        ]);
    }

    /**
     * Indicate that the transaction is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionStatus::COMPLETED(),
            'completed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Indicate that the transaction is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionStatus::PENDING(),
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the transaction is failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionStatus::FAILED(),
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the transaction is for a specific wallet.
     */
    public function forWallet(Wallet $wallet): static
    {
        return $this->state(fn (array $attributes) => [
            'wallet_id' => $wallet->id,
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

    /**
     * Indicate that the transaction has a recipient.
     */
    public function withRecipient(User $recipient): static
    {
        return $this->state(fn (array $attributes) => [
            'recipient_user_id' => $recipient->id,
        ]);
    }
}
