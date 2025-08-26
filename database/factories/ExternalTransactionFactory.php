<?php

namespace Database\Factories;

use App\Enums\ExternalTransactionType;
use App\Enums\PaymentMethod;
use App\Enums\TransactionMode;
use App\Enums\TransactionStatus;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExternalTransaction>
 */
class ExternalTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $transactionType = $this->faker->randomElement(ExternalTransactionType::values());
        $amount = $transactionType === 'recharge'
            ? $this->faker->numberBetween(5000, 100000)
            : $this->faker->numberBetween(1000, 50000);

        return [
            'wallet_id' => Wallet::factory(),
            'amount' => $amount,
            'transaction_type' => $transactionType,
            'mode' => $this->faker->randomElement(TransactionMode::values()),
            'status' => $this->faker->randomElement(TransactionStatus::values()),
            'external_transaction_id' => strtoupper($this->faker->bothify('???_########_????')),
            'description' => $this->faker->sentence(),
            'payment_method' => $this->faker->randomElement(PaymentMethod::values()),
            'gateway_transaction_id' => $this->faker->optional()->uuid(),
            'gateway_response' => null,

            'sender_name' => $this->faker->optional()->name(),
            'sender_account' => $this->faker->optional()->numerify('###########'),
            'receiver_name' => $this->faker->optional()->name(),
            'receiver_account' => $this->faker->optional()->numerify('###########'),
            'created_by' => null, // Ne pas créer automatiquement, laisser les tests définir
            'approved_by' => null, // Ne pas créer automatiquement, laisser les tests définir
            'completed_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'approved_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
