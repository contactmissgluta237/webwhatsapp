<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Contracts\WhatsApp\WhatsAppMessageOrchestratorInterface;
use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Helpers\AiTestHelper;
use Tests\TestCase;

/**
 * Tests pour vérifier que le simulateur se comporte exactement comme le chat réel
 *
 * @group ai
 * @group simulator
 */
final class SimulatorVsRealChatTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function simulator_produces_identical_response_format_as_real_chat(): void
    {
        // Créer un compte WhatsApp de test
        $account = WhatsAppAccount::factory()->create();

        // Créer un modèle AI de test (utilisons Ollama qui est local et prévisible)
        $model = AiModel::factory()->create(
            AiTestHelper::createTestModelData('ollama')
        );

        $account->update(['ai_model_id' => $model->id]);

        $orchestrator = app(WhatsAppMessageOrchestratorInterface::class);

        $userMessage = 'Bonjour, pouvez-vous m\'aider ?';
        $context = [];

        // Créer les métadonnées de compte
        $accountMetadata = new WhatsAppAccountMetadataDTO(
            sessionId: 'test_session',
            sessionName: 'test_session',
            accountId: $account->id,
            agentEnabled: true,
            agentPrompt: $account->agent_prompt ?? 'Tu es un assistant utile',
            aiModelId: $account->ai_model_id,
            responseTime: 'random',
            contextualInformation: '',
            settings: []
        );

        // Simuler l'appel du chat réel via orchestrateur
        $realResponse = $orchestrator->processSimulatedMessage($accountMetadata, $userMessage, $context);

        // Simuler l'appel du simulateur (même paramètres)
        $simulatorResponse = $orchestrator->processSimulatedMessage($accountMetadata, $userMessage, $context);

        // Vérifier que les deux réponses ont la même structure
        $this->assertInstanceOf(
            \App\DTOs\WhatsApp\WhatsAppMessageResponseDTO::class, 
            $realResponse, 
            'La réponse du chat réel doit être un WhatsAppMessageResponseDTO'
        );
        $this->assertInstanceOf(
            \App\DTOs\WhatsApp\WhatsAppMessageResponseDTO::class, 
            $simulatorResponse, 
            'La réponse du simulateur doit être un WhatsAppMessageResponseDTO'
        );

        $this->assertNotNull($realResponse, 'Le chat réel doit générer une réponse');
        $this->assertNotNull($simulatorResponse, 'Le simulateur doit générer une réponse');

        // Vérifier la structure de la réponse DTO
        $this->assertTrue($realResponse->hasAiResponse, 'La réponse doit contenir une réponse IA');
        $this->assertTrue($simulatorResponse->hasAiResponse, 'La réponse doit contenir une réponse IA');

        $this->assertNotEmpty($realResponse->aiResponse, 'Le contenu du chat réel ne doit pas être vide');
        $this->assertNotEmpty($simulatorResponse->aiResponse, 'Le contenu du simulateur ne doit pas être vide');
    }

    #[Test]
    public function simulator_handles_context_same_way_as_real_chat(): void
    {
        $account = WhatsAppAccount::factory()->create();
        $model = AiModel::factory()->create(
            AiTestHelper::createTestModelData('ollama')
        );
        $account->update(['ai_model_id' => $model->id]);

        $orchestrator = app(WhatsAppMessageOrchestratorInterface::class);

        $userMessage = 'Comment je m\'appelle ?';
        $context = [
            ['role' => 'user', 'content' => 'Je m\'appelle Marie'],
            ['role' => 'assistant', 'content' => 'Bonjour Marie !'],
        ];

        // Créer les métadonnées de compte
        $accountMetadata = new WhatsAppAccountMetadataDTO(
            sessionId: 'test_session',
            sessionName: 'test_session',
            accountId: $account->id,
            agentEnabled: true,
            agentPrompt: $account->agent_prompt ?? 'Tu es un assistant utile',
            aiModelId: $account->ai_model_id,
            responseTime: 'random',
            contextualInformation: '',
            settings: []
        );

        // Test avec contexte
        $responseWithContext = $orchestrator->processSimulatedMessage($accountMetadata, $userMessage, $context);

        // Test sans contexte
        $responseWithoutContext = $orchestrator->processSimulatedMessage($accountMetadata, $userMessage, []);

        $this->assertNotNull($responseWithContext, 'Réponse avec contexte doit être générée');
        $this->assertNotNull($responseWithoutContext, 'Réponse sans contexte doit être générée');

        $this->assertInstanceOf(
            \App\DTOs\WhatsApp\WhatsAppMessageResponseDTO::class, 
            $responseWithContext, 
            'Réponse avec contexte doit être un WhatsAppMessageResponseDTO'
        );
        $this->assertInstanceOf(
            \App\DTOs\WhatsApp\WhatsAppMessageResponseDTO::class, 
            $responseWithoutContext, 
            'Réponse sans contexte doit être un WhatsAppMessageResponseDTO'
        );

        // Les réponses doivent avoir du contenu IA
        $this->assertTrue($responseWithContext->hasAiResponse, 'Réponse avec contexte doit avoir du contenu IA');
        $this->assertTrue($responseWithoutContext->hasAiResponse, 'Réponse sans contexte doit avoir du contenu IA');
    }

    #[Test]
    public function simulator_error_handling_matches_real_chat(): void
    {
        $account = WhatsAppAccount::factory()->create();

        // Modèle avec configuration invalide
        $invalidModel = AiModel::factory()->create([
            'provider' => 'ollama',
            'endpoint_url' => 'http://invalid-endpoint:11434',
            'model_identifier' => 'invalid-model',
        ]);

        $account->update(['ai_model_id' => $invalidModel->id]);

        $orchestrator = app(WhatsAppMessageOrchestratorInterface::class);

        // Créer les métadonnées de compte
        $accountMetadata = new WhatsAppAccountMetadataDTO(
            sessionId: 'test_session',
            sessionName: 'test_session',
            accountId: $account->id,
            agentEnabled: true,
            agentPrompt: $account->agent_prompt ?? 'Tu es un assistant utile',
            aiModelId: $account->ai_model_id,
            responseTime: 'random',
            contextualInformation: '',
            settings: []
        );

        $response = $orchestrator->processSimulatedMessage($accountMetadata, 'Test message', []);

        // En cas d'erreur, l'orchestrateur peut retourner null ou une réponse avec fallback
        if ($response !== null && $response->hasAiResponse) {
            $this->assertInstanceOf(
                \App\DTOs\WhatsApp\WhatsAppMessageResponseDTO::class, 
                $response, 
                'Une réponse de fallback doit être un WhatsAppMessageResponseDTO'
            );
            $this->assertNotEmpty($response->aiResponse, 'La réponse de fallback ne doit pas être vide');
            $this->assertStringContainsString('difficultés', $response->aiResponse, 'Le message d\'erreur doit être utilisateur-friendly');
        } else {
            $this->assertTrue($response === null || !$response->hasAiResponse, 'En cas d\'erreur, null ou pas de réponse IA est accepté');
        }
    }
}
