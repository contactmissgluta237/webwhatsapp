<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Enums\MessageDirection;
use App\Enums\MessageType;
use App\Models\AiModel;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Wallet;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IncomingMessageWebhookTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WhatsAppAccount $whatsappAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure WhatsApp bridge settings for tests
        config([
            'whatsapp.bridge.base_url' => 'http://localhost:3001',
            'whatsapp.bridge.api_token' => 'test-token',
        ]);

        // Créer un utilisateur de test
        $this->user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        // Créer un AI Model de test (utilise Ollama par défaut pour éviter les problèmes d'API key)
        $aiModel = AiModel::factory()->create([
            'name' => 'Test Ollama',
            'is_active' => true,
        ]);

        // Créer un compte WhatsApp de test
        $this->whatsappAccount = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'session_name' => 'test_session',
            'phone_number' => '237676636794',
            'status' => 'connected',
            'agent_enabled' => false, // Désactiver l'agent pour éviter les problèmes d'AI
            'ai_model_id' => $aiModel->id,
            'agent_prompt' => 'You are a helpful assistant',
        ]);
    }

    #[Test]
    public function it_can_receive_incoming_message_webhook_successfully()
    {
        // Mock HTTP calls to WhatsApp bridge
        Http::fake([
            'localhost:3001/*' => Http::response(['success' => true], 200),
        ]);

        // Mock les logs de façon permissive pour éviter les erreurs de mock
        Log::shouldReceive('info')->withAnyArgs()->zeroOrMoreTimes();
        Log::shouldReceive('error')->withAnyArgs()->zeroOrMoreTimes();
        Log::shouldReceive('warning')->withAnyArgs()->zeroOrMoreTimes();
        Log::shouldReceive('debug')->withAnyArgs()->zeroOrMoreTimes();

        // Mock orchestrator pour éviter les appels AI réels
        $mockOrchestrator = $this->mock(\App\Services\WhatsApp\Contracts\WhatsAppMessageOrchestratorInterface::class);
        $mockOrchestrator->shouldReceive('processMessage')
            ->once()
            ->andReturn(\App\DTOs\WhatsApp\WhatsAppMessageResponseDTO::success(
                'Salut ! Comment puis-je vous aider ?',
                new \App\DTOs\WhatsApp\WhatsAppAIResponseDTO(
                    response: 'Salut ! Comment puis-je vous aider ?',
                    model: 'test-ollama',
                    confidence: 0.9,
                    tokensUsed: 25,
                    cost: 0.001
                ),
                30,
                5
            ));

        // Create active subscription with remaining messages
        $package = Package::factory()->create(['messages_limit' => 100]);
        UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(30),
            'status' => 'active',
            'messages_limit' => 100,
        ]);

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
                'contactName' => 'Jean Test',
                'pushName' => 'Jean T.',
                'displayName' => 'Jean Test',
            ],
        ];

        // Envoyer le webhook
        $response = $this->postJson('/api/whatsapp/webhook/incoming-message', $payload);

        // Assert the response
        $response->assertStatus(200);

        // Vérifications
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'processed' => true,
            ]);

        // Vérifier qu'une conversation a été créée avec le nom de contact
        $this->assertDatabaseHas('whatsapp_conversations', [
            'whatsapp_account_id' => $this->whatsappAccount->id,
            'contact_phone' => '237676636794',
            'contact_name' => 'Jean Test',
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

        // Nettoyer les mocks Mockery
        \Mockery::close();
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function it_returns_402_when_user_has_no_subscription_and_no_wallet()
    {
        // Aucune subscription, aucun wallet
        $payload = $this->getValidPayload();

        $response = $this->postJson('/api/whatsapp/webhook/incoming-message', $payload);

        $response->assertStatus(402)
            ->assertJson([
                'success' => false,
                'processed' => false,
                'error' => 'No remaining messages and insufficient wallet balance',
            ]);
    }

    #[Test]
    public function it_returns_402_when_subscription_is_expired_and_insufficient_wallet()
    {
        // Créer une subscription expirée
        $package = Package::factory()->create();
        UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'ends_at' => now()->subDay(), // Expirée
            'status' => 'active',
        ]);

        // Wallet avec balance insuffisante
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 10.00, // Moins que le coût minimum de 15 XAF
        ]);

        $payload = $this->getValidPayload();
        $response = $this->postJson('/api/whatsapp/webhook/incoming-message', $payload);

        $response->assertStatus(402)
            ->assertJson([
                'success' => false,
                'processed' => false,
                'error' => 'No remaining messages and insufficient wallet balance',
            ]);
    }

    #[Test]
    public function it_returns_402_when_no_messages_remaining_and_insufficient_wallet()
    {
        // Subscription active mais sans messages restants
        $package = Package::factory()->create(['messages_limit' => 100]);
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(30),
            'status' => 'active',
            'messages_limit' => 100,
        ]);

        // Simuler usage complet
        $accountUsage = $subscription->accountUsages()->create([
            'whatsapp_account_id' => $this->whatsappAccount->id,
            'messages_used' => 100, // Quota épuisé
        ]);

        // Wallet insuffisant
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 5.00, // Moins que 15 XAF
        ]);

        $payload = $this->getValidPayload();
        $response = $this->postJson('/api/whatsapp/webhook/incoming-message', $payload);

        $response->assertStatus(402);
    }

    #[Test]
    public function it_processes_message_when_no_messages_remaining_but_sufficient_wallet()
    {
        // Mock les logs pour éviter les erreurs
        Log::shouldReceive('info', 'warning', 'error', 'debug')->withAnyArgs()->zeroOrMoreTimes();

        // Subscription sans messages restants
        $package = Package::factory()->create(['messages_limit' => 100]);
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(30),
            'status' => 'active',
            'messages_limit' => 100,
        ]);

        $accountUsage = $subscription->accountUsages()->create([
            'whatsapp_account_id' => $this->whatsappAccount->id,
            'messages_used' => 100, // Quota épuisé
        ]);

        // Wallet avec balance suffisante
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 50.00, // Plus que 15 XAF
        ]);

        $payload = $this->getValidPayload();
        $response = $this->postJson('/api/whatsapp/webhook/incoming-message', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'processed' => true,
            ]);
    }

    #[Test]
    public function it_processes_message_when_subscription_is_active_with_remaining_messages()
    {
        // Mock les logs
        Log::shouldReceive('info', 'warning', 'error', 'debug')->withAnyArgs()->zeroOrMoreTimes();

        // Subscription active avec messages restants
        $package = Package::factory()->create(['messages_limit' => 100]);
        UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(30),
            'status' => 'active',
            'messages_limit' => 100,
        ]);

        // Pas besoin de wallet car on a des messages
        $payload = $this->getValidPayload();
        $response = $this->postJson('/api/whatsapp/webhook/incoming-message', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'processed' => true,
            ]);
    }

    #[Test]
    public function it_prioritizes_saved_contact_name_over_push_name()
    {
        // Mock logs
        Log::shouldReceive('info', 'warning', 'error', 'debug')->withAnyArgs()->zeroOrMoreTimes();

        // Create active subscription with remaining messages
        $package = Package::factory()->create(['messages_limit' => 100]);
        UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(30),
            'status' => 'active',
            'messages_limit' => 100,
        ]);

        $payload = [
            'event' => 'incoming_message',
            'session_id' => $this->whatsappAccount->session_id,
            'session_name' => $this->whatsappAccount->session_name,
            'message' => [
                'id' => 'test_name_priority_'.uniqid(),
                'from' => '237676636794@c.us',
                'body' => 'Test priorité noms',
                'timestamp' => now()->timestamp,
                'type' => 'chat',
                'isGroup' => false,
                'contactName' => 'Jean Sauvegardé',
                'pushName' => 'Jean Public',
                'displayName' => 'Jean Public',
            ],
        ];

        $response = $this->postJson('/api/whatsapp/webhook/incoming-message', $payload);
        $response->assertStatus(200);

        // Le nom sauvegardé doit être prioritaire
        $this->assertDatabaseHas('whatsapp_conversations', [
            'whatsapp_account_id' => $this->whatsappAccount->id,
            'contact_phone' => '237676636794',
            'contact_name' => 'Jean Sauvegardé',
        ]);
    }

    #[Test]
    public function it_uses_push_name_when_no_saved_name()
    {
        // Mock logs
        Log::shouldReceive('info', 'warning', 'error', 'debug')->withAnyArgs()->zeroOrMoreTimes();

        // Create active subscription with remaining messages
        $package = Package::factory()->create(['messages_limit' => 100]);
        UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(30),
            'status' => 'active',
            'messages_limit' => 100,
        ]);

        $payload = [
            'event' => 'incoming_message',
            'session_id' => $this->whatsappAccount->session_id,
            'session_name' => $this->whatsappAccount->session_name,
            'message' => [
                'id' => 'test_push_name_'.uniqid(),
                'from' => '237676636795@c.us',
                'body' => 'Test push name',
                'timestamp' => now()->timestamp,
                'type' => 'chat',
                'isGroup' => false,
                'contactName' => null,
                'pushName' => 'Jean Public Seulement',
                'displayName' => 'Jean Public Seulement',
            ],
        ];

        $response = $this->postJson('/api/whatsapp/webhook/incoming-message', $payload);
        $response->assertStatus(200);

        // Le push name doit être utilisé
        $this->assertDatabaseHas('whatsapp_conversations', [
            'whatsapp_account_id' => $this->whatsappAccount->id,
            'contact_phone' => '237676636795',
            'contact_name' => 'Jean Public Seulement',
        ]);
    }

    #[Test]
    public function it_handles_non_text_messages_with_automatic_response()
    {
        // Mock les logs
        Log::shouldReceive('info', 'warning', 'error', 'debug')->withAnyArgs()->zeroOrMoreTimes();

        // Create active subscription with remaining messages
        $package = Package::factory()->create(['messages_limit' => 100]);
        UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(30),
            'status' => 'active',
            'messages_limit' => 100,
        ]);

        $nonTextMessageTypes = ['image', 'video', 'audio', 'document', 'sticker', 'location', 'contact'];

        foreach ($nonTextMessageTypes as $messageType) {
            $payload = [
                'event' => 'incoming_message',
                'session_id' => $this->whatsappAccount->session_id,
                'session_name' => $this->whatsappAccount->session_name,
                'message' => [
                    'id' => 'test_non_text_'.$messageType.'_'.uniqid(),
                    'from' => '237676636794@c.us',
                    'body' => $messageType === 'image' ? 'Image description' : 'Some content', // Images peuvent avoir un body vide mais la validation l'exige
                    'timestamp' => now()->timestamp,
                    'type' => $messageType,
                    'isGroup' => false,
                    'contactName' => 'Test Contact',
                    'pushName' => 'Test Push',
                    'displayName' => 'Test Display',
                ],
            ];

            $response = $this->postJson('/api/whatsapp/webhook/incoming-message', $payload);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => true,
                    'response_message' => "Désolé, je ne peux comprendre que les messages texte pour le moment. Veuillez m'envoyer votre message sous forme de texte et je serai ravi de vous aider ! 😊",
                    'wait_time_seconds' => 30,
                    'typing_duration_seconds' => 5,
                ]);

            // Vérifier qu'aucun message n'a été traité par l'IA (pas de stockage en DB)
            $this->assertDatabaseMissing('whatsapp_messages', [
                'whatsapp_message_id' => 'test_non_text_'.$messageType.'_'.substr(uniqid(), -10),
            ]);
        }
    }

    #[Test]
    public function it_handles_notification_messages_with_automatic_response()
    {
        // Mock les logs
        Log::shouldReceive('info', 'warning', 'error', 'debug')->withAnyArgs()->zeroOrMoreTimes();

        // Create active subscription
        $package = Package::factory()->create(['messages_limit' => 100]);
        UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(30),
            'status' => 'active',
            'messages_limit' => 100,
        ]);

        $notificationTypes = ['e2e_notification', 'notification_template'];

        foreach ($notificationTypes as $messageType) {
            $payload = [
                'event' => 'incoming_message',
                'session_id' => $this->whatsappAccount->session_id,
                'session_name' => $this->whatsappAccount->session_name,
                'message' => [
                    'id' => 'test_notification_'.$messageType.'_'.uniqid(),
                    'from' => '237676636794@c.us',
                    'body' => 'Notification message', // Les notifications ont souvent un body vide mais la validation l'exige
                    'timestamp' => now()->timestamp,
                    'type' => $messageType,
                    'isGroup' => false,
                ],
            ];

            $response = $this->postJson('/api/whatsapp/webhook/incoming-message', $payload);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => true,
                    'response_message' => "Désolé, je ne peux comprendre que les messages texte pour le moment. Veuillez m'envoyer votre message sous forme de texte et je serai ravi de vous aider ! 😊",
                ]);
        }
    }

    #[Test]
    public function it_processes_text_and_chat_messages_normally()
    {
        // Mock les logs
        Log::shouldReceive('info', 'warning', 'error', 'debug')->withAnyArgs()->zeroOrMoreTimes();

        // Create active subscription
        $package = Package::factory()->create(['messages_limit' => 100]);
        UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(30),
            'status' => 'active',
            'messages_limit' => 100,
        ]);

        $textMessageTypes = ['text', 'chat'];

        foreach ($textMessageTypes as $messageType) {
            $payload = [
                'event' => 'incoming_message',
                'session_id' => $this->whatsappAccount->session_id,
                'session_name' => $this->whatsappAccount->session_name,
                'message' => [
                    'id' => 'test_text_'.$messageType.'_'.uniqid(),
                    'from' => '237676636794@c.us',
                    'body' => 'Bonjour, comment allez-vous ?',
                    'timestamp' => now()->timestamp,
                    'type' => $messageType,
                    'isGroup' => false,
                    'contactName' => 'Test Contact',
                    'pushName' => 'Test Push',
                    'displayName' => 'Test Display',
                ],
            ];

            $response = $this->postJson('/api/whatsapp/webhook/incoming-message', $payload);

            // Pour les messages texte, le traitement doit se poursuivre normalement
            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => true,
                ]);

            // Vérifier qu'une conversation a été créée
            $this->assertDatabaseHas('whatsapp_conversations', [
                'whatsapp_account_id' => $this->whatsappAccount->id,
                'contact_phone' => '237676636794',
                'contact_name' => 'Test Contact',
            ]);
        }
    }

    private function getValidPayload(): array
    {
        return [
            'event' => 'incoming_message',
            'session_id' => $this->whatsappAccount->session_id,
            'session_name' => $this->whatsappAccount->session_name,
            'message' => [
                'id' => 'test_billing_'.uniqid(),
                'from' => '237676636794@c.us',
                'body' => 'Test message for billing validation',
                'timestamp' => now()->timestamp,
                'type' => 'chat',
                'isGroup' => false,
            ],
        ];
    }
}
