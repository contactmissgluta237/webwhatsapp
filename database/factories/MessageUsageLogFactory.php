<?php

namespace Database\Factories;

use App\Enums\BillingType;
use App\Models\User;
use App\Models\WhatsAppAccountUsage;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MessageUsageLog>
 */
class MessageUsageLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $aiCost = config('whatsapp.billing.costs.ai_message', 15);
        $productCount = fake()->numberBetween(0, 2);
        $productCost = $productCount * config('whatsapp.billing.costs.product_message', 10);
        $mediaCount = fake()->numberBetween(0, 3);
        $mediaCost = $mediaCount * config('whatsapp.billing.costs.media', 5);
        $totalCost = $aiCost + $productCost + $mediaCost;

        return [
            'whatsapp_message_id' => WhatsAppMessage::factory(),
            'whatsapp_account_usage_id' => fake()->boolean(70) ? WhatsAppAccountUsage::factory() : null,
            'whatsapp_conversation_id' => WhatsAppConversation::factory(),
            'user_id' => User::factory(),
            'ai_message_cost' => $aiCost,
            'product_messages_count' => $productCount,
            'product_messages_cost' => $productCost,
            'media_count' => $mediaCount,
            'media_cost' => $mediaCost,
            'total_cost' => $totalCost,
            'billing_type' => fake()->randomElement(BillingType::cases()),
        ];
    }

    /**
     * State pour débit wallet direct (sans subscription)
     */
    public function walletDirect(): static
    {
        return $this->state(fn (array $attributes) => [
            'whatsapp_account_usage_id' => null,
            'billing_type' => BillingType::WALLET_DIRECT,
        ]);
    }

    public function subscriptionQuota(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_type' => BillingType::SUBSCRIPTION_QUOTA,
        ]);
    }

    public function aiOnly(): static
    {
        $aiCost = config('whatsapp.billing.costs.ai_message', 15);

        return $this->state(fn (array $attributes) => [
            'ai_message_cost' => $aiCost,
            'product_messages_count' => 0,
            'product_messages_cost' => 0,
            'media_count' => 0,
            'media_cost' => 0,
            'total_cost' => $aiCost,
        ]);
    }

    public function withProductsAndMedia(): static
    {
        $aiCost = config('whatsapp.billing.costs.ai_message', 15);
        $productCount = fake()->numberBetween(1, 3);
        $productCost = $productCount * config('whatsapp.billing.costs.product_message', 10);
        $mediaCount = fake()->numberBetween(1, 5);
        $mediaCost = $mediaCount * config('whatsapp.billing.costs.media', 5);
        $totalCost = $aiCost + $productCost + $mediaCost;

        return $this->state(fn (array $attributes) => [
            'ai_message_cost' => $aiCost,
            'product_messages_count' => $productCount,
            'product_messages_cost' => $productCost,
            'media_count' => $mediaCount,
            'media_cost' => $mediaCost,
            'total_cost' => $totalCost,
        ]);
    }
}
