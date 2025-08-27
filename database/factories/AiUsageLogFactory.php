<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiUsageLog>
 */
class AiUsageLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $promptTokens = $this->faker->numberBetween(20, 200);
        $completionTokens = $this->faker->numberBetween(10, 150);
        $cachedTokens = $this->faker->numberBetween(0, 50);
        $totalTokens = $promptTokens + $completionTokens;

        // DeepSeek pricing: $0.14/1M prompt, $0.28/1M completion, $0.014/1M cached
        $promptCostUsd = ($promptTokens / 1_000_000) * 0.14;
        $completionCostUsd = ($completionTokens / 1_000_000) * 0.28;
        $cachedCostUsd = ($cachedTokens / 1_000_000) * 0.014;
        $totalCostUsd = $promptCostUsd + $completionCostUsd + $cachedCostUsd;
        $totalCostXaf = $totalCostUsd * 650; // USD to XAF conversion

        return [
            'user_id' => \App\Models\User::factory(),
            'whatsapp_account_id' => \App\Models\WhatsAppAccount::factory(),
            'whatsapp_conversation_id' => \App\Models\WhatsAppConversation::factory(),
            'whatsapp_message_id' => \App\Models\WhatsAppMessage::factory(),
            'ai_model' => 'deepseek-chat',
            'provider' => 'deepseek',
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'cached_tokens' => $cachedTokens,
            'prompt_cost_usd' => round($promptCostUsd, 6),
            'completion_cost_usd' => round($completionCostUsd, 6),
            'cached_cost_usd' => round($cachedCostUsd, 6),
            'total_cost_usd' => round($totalCostUsd, 6),
            'total_cost_xaf' => round($totalCostXaf, 2),
            'request_length' => $this->faker->numberBetween(5, 500),
            'response_length' => $this->faker->numberBetween(10, 1000),
            'api_attempts' => 1,
            'response_time_ms' => $this->faker->numberBetween(200, 3000),
        ];
    }
}
