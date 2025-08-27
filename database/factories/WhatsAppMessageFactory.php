<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WhatsAppMessage>
 */
class WhatsAppMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'whatsapp_conversation_id' => \App\Models\WhatsAppConversation::factory(),
            'whatsapp_message_id' => $this->faker->unique()->regexify('msg_[a-zA-Z0-9]{10}'),
            'direction' => $this->faker->randomElement([\App\Enums\MessageDirection::INBOUND(), \App\Enums\MessageDirection::OUTBOUND()]),
            'content' => $this->faker->sentence,
            'message_type' => \App\Enums\MessageType::TEXT(),
            'message_subtype' => \App\Enums\MessageSubtype::MAIN(),
            'media_urls' => null,
            'is_ai_generated' => false,
            'ai_model_used' => null,
            'ai_confidence' => null,
            'processed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
