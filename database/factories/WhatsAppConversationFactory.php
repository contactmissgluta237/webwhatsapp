<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WhatsAppConversation>
 */
class WhatsAppConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'whatsapp_account_id' => \App\Models\WhatsAppAccount::factory(),
            'chat_id' => $this->faker->unique()->numerify('2376########@c.us'),
            'contact_phone' => $this->faker->phoneNumber,
            'contact_name' => $this->faker->name,
            'is_group' => false,
            'last_message_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'unread_count' => $this->faker->numberBetween(0, 5),
            'is_ai_enabled' => true,
        ];
    }
}
