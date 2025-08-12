<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AiModel;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WhatsAppAccount>
 */
final class WhatsAppAccountFactory extends Factory
{
    protected $model = WhatsAppAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'session_name' => $this->faker->unique()->userName(),
            'session_id' => $this->faker->unique()->uuid(),
            'phone_number' => null,
            'status' => 'disconnected',
            'qr_code' => null,
            'last_seen_at' => now(),
            'session_data' => null,
            'agent_name' => null,
            'agent_enabled' => false,
            'ai_model_id' => null,
            'response_time' => 'random',
            'agent_prompt' => 'Tu es un assistant WhatsApp utile et professionnel.',
            'trigger_words' => null,
            'stop_on_human_reply' => false,
            'last_ai_response_at' => null,
            'daily_ai_responses' => 0,
        ];
    }

    public function withAi(?AiModel $model = null): static
    {
        return $this->state(function (array $attributes) use ($model) {
            $aiModel = $model ?? AiModel::factory()->ollama()->create();

            return [
                'agent_name' => 'Assistant IA',
                'agent_enabled' => true,
                'ai_model_id' => $aiModel->id,
                'agent_prompt' => 'Tu es un assistant commercial pour une boutique en ligne. Sois sympathique et professionnel.',
                'trigger_words' => json_encode(['aide', 'support', 'info']),
                'response_time' => 'random',
            ];
        });
    }

    public function connected(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'connected',
                'phone_number' => '+'.$this->faker->numerify('##########'),
                'last_seen_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            ];
        });
    }

    public function disconnected(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'disconnected',
                'last_seen_at' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
            ];
        });
    }
}
