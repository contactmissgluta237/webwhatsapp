<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WhatsAppWebhooksTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected WhatsAppAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les rôles nécessaires
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);

        // Créer un pays pour éviter les erreurs de validation
        \Illuminate\Support\Facades\DB::table('countries')->insert([
            'id' => 1,
            'name' => 'Cameroon',
            'code' => 'CM',
            'phone_code' => '+237',
            'flag_emoji' => '🇨🇲',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->user = User::factory()->create();
        $this->user->assignRole('customer');
        $this->user = $this->user->fresh();

        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function incoming_message_webhook_accepts_valid_payload(): void
    {
        $payload = [
            'sessionId' => $this->account->session_id,
            'chatId' => '237655332183@c.us',
            'messageId' => 'message_123',
            'messageText' => 'Hello, this is a test message',
            'senderName' => 'John Doe',
            'timestamp' => now()->timestamp,
        ];

        $this->post('/api/whatsapp/webhook/incoming-message', $payload)
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    }

    #[Test]
    public function incoming_message_webhook_requires_valid_session(): void
    {
        $payload = [
            'sessionId' => 'non-existent-session',
            'chatId' => '237655332183@c.us',
            'messageId' => 'message_123',
            'messageText' => 'Hello, this is a test message',
            'senderName' => 'John Doe',
            'timestamp' => now()->timestamp,
        ];

        $this->post('/api/whatsapp/webhook/incoming-message', $payload)
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Session not found',
            ]);
    }

    #[Test]
    public function session_connected_webhook_accepts_valid_payload(): void
    {
        $payload = [
            'sessionId' => $this->account->session_id,
            'phoneNumber' => '237655332183',
            'status' => 'connected',
            'timestamp' => now()->timestamp,
        ];

        $this->post('/api/whatsapp/webhook/session-connected', $payload)
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    }

    #[Test]
    public function session_connected_webhook_requires_valid_session(): void
    {
        $payload = [
            'sessionId' => 'non-existent-session',
            'phoneNumber' => '237655332183',
            'status' => 'connected',
            'timestamp' => now()->timestamp,
        ];

        $this->post('/api/whatsapp/webhook/session-connected', $payload)
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Session not found',
            ]);
    }

    #[Test]
    public function webhooks_require_required_fields(): void
    {
        // Test incoming message without required fields
        $this->post('/api/whatsapp/webhook/incoming-message', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sessionId', 'chatId', 'messageText']);

        // Test session connected without required fields
        $this->post('/api/whatsapp/webhook/session-connected', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sessionId', 'phoneNumber', 'status']);
    }
}