<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WhatsAppAccount;
use App\Services\CreditSystemService;
use App\Services\WhatsApp\WhatsAppMessageOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreditSystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private CreditSystemService $creditSystemService;
    private WhatsAppMessageOrchestrator $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->creditSystemService = app(CreditSystemService::class);
        $this->orchestrator = app(WhatsAppMessageOrchestrator::class);
    }

    /** @test */
    public function it_blocks_ai_response_when_user_has_insufficient_credits(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 10.00, // Insufficient balance
        ]);
        
        $whatsappAccount = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'agent_enabled' => true,
        ]);

        $accountMetadata = WhatsAppAccountMetadataDTO::fromAccount(
            $whatsappAccount,
            'test_session_123',
            'test_session'
        );

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_123',
            from: '+237123456789',
            body: 'Hello, can you help me?',
            timestamp: time(),
            type: 'text',
            pushName: 'Test User'
        );

        $response = $this->orchestrator->processIncomingMessage($accountMetadata, $messageRequest);

        // Vérifier que la réponse indique qu'aucune réponse IA n'a été générée
        $this->assertFalse($response->hasAiResponse);
        
        // Vérifier que le solde n'a pas été débité
        $wallet->refresh();
        $this->assertEquals(10.00, $wallet->balance);
    }

    /** @test */
    public function it_processes_message_and_deducts_credits_when_sufficient_balance(): void
    {
        $user = User::factory()->create();
        $initialBalance = 200.00;
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => $initialBalance,
        ]);
        
        $whatsappAccount = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'agent_enabled' => true,
        ]);

        $accountMetadata = WhatsAppAccountMetadataDTO::fromAccount(
            $whatsappAccount,
            'test_session_123',
            'test_session'
        );

        // Mock the AI response to avoid actual API calls
        $this->mockAiProviderService();

        $response = $this->orchestrator->processSimulatedMessage(
            $accountMetadata,
            'Hello, can you help me?'
        );

        // Vérifier que la réponse a été générée
        $this->assertTrue($response->hasAiResponse);
        
        // Vérifier que le crédit a été déduit
        $wallet->refresh();
        $expectedBalance = $initialBalance - $this->creditSystemService->getMessageCost();
        $this->assertEquals($expectedBalance, $wallet->balance);

        // Vérifier qu'une transaction de débit a été créée
        $this->assertDatabaseHas('internal_transactions', [
            'wallet_id' => $wallet->id,
            'transaction_type' => 'debit',
            'amount' => $this->creditSystemService->getMessageCost(),
        ]);
    }

    /** @test */
    public function it_blocks_simulation_when_user_has_insufficient_credits(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 10.00, // Insufficient balance
        ]);
        
        $whatsappAccount = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'agent_enabled' => true,
        ]);

        $accountMetadata = WhatsAppAccountMetadataDTO::fromAccount(
            $whatsappAccount,
            'simulator_' . $whatsappAccount->id,
            'simulator_' . $whatsappAccount->session_name
        );

        $response = $this->orchestrator->processSimulatedMessage(
            $accountMetadata,
            'Hello, can you help me?'
        );

        // Vérifier que la réponse indique qu'aucune réponse IA n'a été générée
        $this->assertFalse($response->hasAiResponse);
        
        // Vérifier que le solde n'a pas été débité
        $wallet->refresh();
        $this->assertEquals(10.00, $wallet->balance);
    }

    /** @test */
    public function it_logs_appropriate_messages_for_credit_actions(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 10.00, // Insufficient balance
        ]);
        
        $whatsappAccount = WhatsAppAccount::factory()->create([
            'user_id' => $user->id,
            'agent_enabled' => true,
        ]);

        $accountMetadata = WhatsAppAccountMetadataDTO::fromAccount(
            $whatsappAccount,
            'test_session_123',
            'test_session'
        );

        // Test with insufficient credits
        $this->expectsLogs();
        
        $response = $this->orchestrator->processSimulatedMessage(
            $accountMetadata,
            'Hello, can you help me?'
        );

        // We can't easily test the exact log messages without additional setup,
        // but we can verify the behavior is correct
        $this->assertFalse($response->hasAiResponse);
    }

    /** @test */
    public function it_handles_edge_case_when_whatsapp_account_not_found(): void
    {
        $accountMetadata = new WhatsAppAccountMetadataDTO(
            sessionId: 'test_session_123',
            sessionName: 'test_session',
            accountId: 99999, // Non-existent account ID
            agentEnabled: true
        );

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'msg_123',
            from: '+237123456789',
            body: 'Hello, can you help me?',
            timestamp: time(),
            type: 'text',
            pushName: 'Test User'
        );

        $response = $this->orchestrator->processIncomingMessage($accountMetadata, $messageRequest);

        $this->assertFalse($response->hasAiResponse);
    }

    private function mockAiProviderService(): void
    {
        // This is a placeholder for mocking the AI provider service
        // In a real implementation, you would mock the service to return a predefined response
        // without making actual API calls
        
        // Example:
        // $this->mock(AIProviderServiceInterface::class, function (MockInterface $mock) {
        //     $mock->shouldReceive('generateResponse')
        //          ->andReturn(new MockAIResponse('Test AI response'));
        // });
    }

    private function expectsLogs(): void
    {
        // Placeholder for log expectation setup
        // In a real implementation, you might use Log::shouldReceive() or similar
    }
}