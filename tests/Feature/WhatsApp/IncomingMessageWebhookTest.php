<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Enums\MessageDirection;
use App\Enums\MessageType;
use App\Models\WhatsAppMessage;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class IncomingMessageWebhookTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WhatsAppAccount $whatsappAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un utilisateur de test
        $this->user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        // Créer un compte WhatsApp de test
        $this->whatsappAccount = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'session_name' => 'test_session',
            'phone_number' => '237676636794',
            'status' => 'connected',
            'agent_enabled' => true,
        ]);
    }

    /** @test */
    public function it_can_receive_incoming_message_webhook_successfully()
    {
        // Mock les logs de façon permissive pour éviter les erreurs de mock
        Log::shouldReceive('info')->withAnyArgs()->zeroOrMoreTimes();
        Log::shouldReceive('error')->withAnyArgs()->zeroOrMoreTimes();
        Log::shouldReceive('warning')->withAnyArgs()->zeroOrMoreTimes();
        Log::shouldReceive('debug')->withAnyArgs()->zeroOrMoreTimes();

        // Payload du webhook simulant Node.js
        $payload = [
            'event' => 'incoming_message',
            'session_id' => $this->whatsappAccount->session_id,
            'session_name' => $this->whatsappAccount->session_name,
            'message' => [
                'id' => 'false_237676636794@c.us_3EB09F559617982ED9801A',
                'from' => '237676636794@c.us',
                'body' => 'Salut grand, comment ça va ?',
                'timestamp' => now()->timestamp,
                'type' => 'chat',
                'isGroup' => false,
            ],
        ];

        // Envoyer le webhook
        $response = $this->postJson('/api/whatsapp/webhook/incoming-message', $payload);

        // Debug: Afficher la réponse si elle échoue
        if ($response->getStatusCode() !== 200) {
            dump('Response Status: ' . $response->getStatusCode());
            dump('Response Content: ' . $response->getContent());
            dump('Response Headers: ', $response->headers->all());
        }

        // Vérifications
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'processed' => true,
            ]);

        // Vérifier qu'une conversation a été créée
        $this->assertDatabaseHas('whatsapp_conversations', [
            'whatsapp_account_id' => $this->whatsappAccount->id,
            'contact_phone' => '237676636794',
        ]);

        // Vérifier qu'un message a été stocké avec les bons Enums
        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_message_id' => 'false_237676636794@c.us_3EB09F559617982ED9801A',
            'content' => 'Salut grand, comment ça va ?',
            'direction' => MessageDirection::INBOUND()->value,
            'message_type' => MessageType::TEXT()->value,
            'is_ai_generated' => false,
        ]);
    }

    /** @test */
    public function it_handles_group_messages_correctly()
    {
        $payload = [
            'event' => 'incoming_message',
            'session_id' => $this->whatsappAccount->session_id,
            'session_name' => $this->whatsappAccount->session_name,
            'message' => [
                'id' => 'false_23790128226-1501499582@g.us_BD963D08001CF3F969F95449BAF750F3_237670149417@c.us',
                'from' => '23790128226-1501499582@g.us',
                'body' => 'Message de groupe test',
                'timestamp' => now()->timestamp,
                'type' => 'chat',
                'isGroup' => true,
            ],
        ];

        $response = $this->postJson('/api/whatsapp/webhook/incoming-message', $payload);

        $response->assertStatus(200);

        // Vérifier qu'une conversation de groupe a été créée
        $this->assertDatabaseHas('whatsapp_conversations', [
            'whatsapp_account_id' => $this->whatsappAccount->id,
            'contact_phone' => '23790128226-1501499582',
        ]);
    }

    /** @test */
    public function it_fails_with_invalid_session_id_type()
    {
        $payload = [
            'event' => 'incoming_message',
            'session_id' => 123, // Number au lieu de string
            'session_name' => $this->whatsappAccount->session_name,
            'message' => [
                'id' => 'test_message_id',
                'from' => '237676636794@c.us',
                'body' => 'Test message',
                'timestamp' => now()->timestamp,
                'type' => 'chat',
                'isGroup' => false,
            ],
        ];

        $response = $this->postJson('/api/whatsapp/webhook/incoming-message', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['session_id']);
    }

    /** @test */
    public function it_handles_missing_whatsapp_account_gracefully()
    {
        $payload = [
            'event' => 'incoming_message',
            'session_id' => '999', // ID inexistant
            'session_name' => 'nonexistent_session',
            'message' => [
                'id' => 'test_message_id',
                'from' => '237676636794@c.us',
                'body' => 'Test message',
                'timestamp' => now()->timestamp,
                'type' => 'chat',
                'isGroup' => false,
            ],
        ];

        Log::shouldReceive('warning')
            ->with('WhatsApp account not found for incoming message', \Mockery::any())
            ->once();

        // Mock tous les types de logs possibles pour éviter les erreurs
        Log::shouldReceive('error')
            ->zeroOrMoreTimes();

        Log::shouldReceive('info')
            ->zeroOrMoreTimes();

        $response = $this->postJson('/api/whatsapp/webhook/incoming-message', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'processed' => true,
            ]);

        // Aucun message ne devrait être stocké
        $this->assertDatabaseCount('whatsapp_messages', 0);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/whatsapp/webhook/incoming-message', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'session_id',
                'session_name',
                'message',
            ]);
    }

    /** @test */
    public function it_validates_message_structure()
    {
        $payload = [
            'event' => 'incoming_message',
            'session_id' => $this->whatsappAccount->session_id,
            'session_name' => $this->whatsappAccount->session_name,
            'message' => [
                // Manque des champs requis
                'from' => '237676636794@c.us',
            ],
        ];

        $response = $this->postJson('/api/whatsapp/webhook/incoming-message', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'message.id',
                'message.body',
                'message.timestamp',
                'message.type',
            ]);
    }

    /** @test */
    public function it_creates_enums_correctly_for_different_message_types()
    {
        // Test avec différents types de messages pour valider les Enums
        $messageTypes = [
            ['type' => 'chat', 'expectedEnum' => MessageType::TEXT()],
            // On peut ajouter d'autres types plus tard
        ];

        foreach ($messageTypes as $testCase) {
            $payload = [
                'event' => 'incoming_message',
                'session_id' => $this->whatsappAccount->session_id,
                'session_name' => $this->whatsappAccount->session_name,
                'message' => [
                    'id' => 'test_message_'.$testCase['type'],
                    'from' => '237676636794@c.us',
                    'body' => 'Test message '.$testCase['type'],
                    'timestamp' => now()->timestamp,
                    'type' => $testCase['type'],
                    'isGroup' => false,
                ],
            ];

            $response = $this->postJson('/api/whatsapp/webhook/incoming-message', $payload);

            $response->assertStatus(200);

            // Vérifier que le message a été créé avec les bonnes valeurs
            $message = WhatsAppMessage::where('whatsapp_message_id', 'test_message_'.$testCase['type'])->first();

            $this->assertEquals('inbound', $message->direction);
            $this->assertEquals('text', $message->message_type);
        }
    }
}
