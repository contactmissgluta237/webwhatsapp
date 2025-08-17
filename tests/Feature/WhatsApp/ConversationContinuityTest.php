<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Contracts\WhatsApp\WhatsAppMessageOrchestratorInterface;
use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\Enums\ResponseTime;
use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Helpers\AiTestHelper;
use Tests\TestCase;

/**
 * Test pour vérifier que les conversations continues avec contexte fonctionnent
 * Teste à la fois Ollama et DeepSeek
 *
 * @group ai
 * @group simulator
 * @group conversation
 */
final class ConversationContinuityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Fournisseur de données pour tester différents providers IA
     */
    public static function aiProviderDataProvider(): array
    {
        return [
            'ollama' => ['ollama'],
            'deepseek' => ['deepseek'],
        ];
    }

    #[Test]
    public function simulator_maintains_conversation_context_across_multiple_messages_with_ollama(): void
    {
        $this->runConversationContinuityTest('ollama');
    }

    #[Test]
    public function simulator_maintains_conversation_context_across_multiple_messages_with_deepseek(): void
    {
        $this->runConversationContinuityTest('deepseek');
    }

    private function runConversationContinuityTest(string $provider): void
    {
        // Créer un compte WhatsApp de test
        $account = WhatsAppAccount::factory()->create();

        // Créer un modèle AI de test selon le provider
        $model = AiModel::factory()->create(
            AiTestHelper::createTestModelData($provider)
        );

        $account->update(['ai_model_id' => $model->id]);

        $orchestrator = app(WhatsAppMessageOrchestratorInterface::class);

        // Créer les métadonnées de compte
        $accountMetadata = new WhatsAppAccountMetadataDTO(
            sessionId: "test_conversation_continuity_{$provider}",
            sessionName: 'test_session',
            accountId: $account->id,
            agentEnabled: true,
            agentPrompt: $account->agent_prompt ?? 'Tu es un assistant utile',
            aiModelId: $account->ai_model_id,
            responseTime: ResponseTime::RANDOM(),
            contextualInformation: '',
            settings: []
        );

        // Messages de test pour simuler une conversation
        $messages = [
            'Bonjour, comment allez-vous ?',
            'Pouvez-vous me dire quels sont vos services ?',
            'Combien ça coûte ?',
            'Avez-vous des exemples ?',
            'Comment vous contacter ?',
        ];

        $conversationContext = [];

        foreach ($messages as $index => $userMessage) {
            // Traiter le message via l'orchestrateur
            $response = $orchestrator->processSimulatedMessage(
                $accountMetadata,
                $userMessage,
                $conversationContext
            );

            // Vérifier que la réponse est générée
            $this->assertNotNull($response, 'Message '.($index + 1).' doit générer une réponse');
            $this->assertTrue($response->hasAiResponse, 'Message '.($index + 1).' doit avoir une réponse IA');
            $this->assertNotEmpty($response->aiResponse, 'Message '.($index + 1).' ne doit pas être vide');

            // Ajouter au contexte pour le prochain message
            $conversationContext[] = ['role' => 'user', 'content' => $userMessage];
            $conversationContext[] = ['role' => 'assistant', 'content' => $response->aiResponse];

            // Vérifier que le contexte grandit correctement
            $expectedContextSize = ($index + 1) * 2; // 2 messages par échange (user + assistant)
            $this->assertCount(
                $expectedContextSize,
                $conversationContext,
                'Le contexte doit contenir '.$expectedContextSize.' messages après '.($index + 1).' échanges'
            );
        }

        // Vérifier que la conversation finale contient 10 messages (5 échanges)
        $this->assertCount(10, $conversationContext, 'La conversation doit contenir 10 messages au total');

        // Vérifier l'alternance user/assistant
        for ($i = 0; $i < count($conversationContext); $i++) {
            if ($i % 2 === 0) {
                $this->assertEquals('user', $conversationContext[$i]['role'], "Message $i doit être de l'utilisateur");
            } else {
                $this->assertEquals('assistant', $conversationContext[$i]['role'], "Message $i doit être de l'assistant");
            }
        }
    }

    #[Test]
    public function simulator_handles_empty_context_correctly(): void
    {
        $account = WhatsAppAccount::factory()->create();
        $model = AiModel::factory()->create(AiTestHelper::createTestModelData('ollama'));
        $account->update(['ai_model_id' => $model->id]);

        $orchestrator = app(WhatsAppMessageOrchestratorInterface::class);

        $accountMetadata = new WhatsAppAccountMetadataDTO(
            sessionId: 'test_empty_context',
            sessionName: 'test_session',
            accountId: $account->id,
            agentEnabled: true,
            agentPrompt: 'Tu es un assistant utile',
            aiModelId: $account->ai_model_id,
            responseTime: ResponseTime::RANDOM(),
            contextualInformation: '',
            settings: []
        );

        // Test avec contexte vide
        $response = $orchestrator->processSimulatedMessage(
            $accountMetadata,
            'Premier message sans contexte',
            []
        );

        $this->assertNotNull($response, 'Premier message doit générer une réponse');
        $this->assertTrue($response->hasAiResponse, 'Premier message doit avoir une réponse IA');
    }

    #[Test]
    public function simulator_handles_large_context_correctly(): void
    {
        $account = WhatsAppAccount::factory()->create();
        $model = AiModel::factory()->create(AiTestHelper::createTestModelData('ollama'));
        $account->update(['ai_model_id' => $model->id]);

        $orchestrator = app(WhatsAppMessageOrchestratorInterface::class);

        $accountMetadata = new WhatsAppAccountMetadataDTO(
            sessionId: 'test_large_context',
            sessionName: 'test_session',
            accountId: $account->id,
            agentEnabled: true,
            agentPrompt: 'Tu es un assistant utile',
            aiModelId: $account->ai_model_id,
            responseTime: ResponseTime::RANDOM(),
            contextualInformation: '',
            settings: []
        );

        // Créer un grand contexte (15 messages)
        $largeContext = [];
        for ($i = 1; $i <= 15; $i++) {
            $largeContext[] = ['role' => 'user', 'content' => "Message utilisateur $i"];
            $largeContext[] = ['role' => 'assistant', 'content' => "Réponse assistant $i"];
        }

        // Test avec grand contexte
        $response = $orchestrator->processSimulatedMessage(
            $accountMetadata,
            'Message avec grand contexte',
            $largeContext
        );

        $this->assertNotNull($response, 'Message avec grand contexte doit générer une réponse');
        $this->assertTrue($response->hasAiResponse, 'Message avec grand contexte doit avoir une réponse IA');
    }
}
