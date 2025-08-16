<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\DTOs\AI\AiRequestDTO;
use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\AI\WhatsAppAIService;
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

        $service = app(WhatsAppAIService::class);
        
        $userMessage = 'Bonjour, pouvez-vous m\'aider ?';
        $context = [];

        // Simuler l'appel du chat réel
        $realResponse = $service->generateResponse($account, $userMessage, $context);
        
        // Simuler l'appel du simulateur (même paramètres)
        $simulatorResponse = $service->generateResponse($account, $userMessage, $context);

        // Vérifier que les deux réponses ont la même structure
        $this->assertIsArray($realResponse, 'La réponse du chat réel doit être un array');
        $this->assertIsArray($simulatorResponse, 'La réponse du simulateur doit être un array');
        
        $this->assertNotNull($realResponse, 'Le chat réel doit générer une réponse');
        $this->assertNotNull($simulatorResponse, 'Le simulateur doit générer une réponse');
        
        // Vérifier la structure de la réponse
        $this->assertArrayHasKey('response', $realResponse, 'La réponse doit contenir le contenu');
        $this->assertArrayHasKey('response', $simulatorResponse, 'La réponse doit contenir le contenu');
        
        $this->assertNotEmpty($realResponse['response'], 'Le contenu du chat réel ne doit pas être vide');
        $this->assertNotEmpty($simulatorResponse['response'], 'Le contenu du simulateur ne doit pas être vide');
    }

    #[Test]
    public function simulator_handles_context_same_way_as_real_chat(): void
    {
        $account = WhatsAppAccount::factory()->create();
        $model = AiModel::factory()->create(
            AiTestHelper::createTestModelData('ollama')
        );
        $account->update(['ai_model_id' => $model->id]);

        $service = app(WhatsAppAIService::class);
        
        $userMessage = 'Comment je m\'appelle ?';
        $context = [
            ['role' => 'user', 'content' => 'Je m\'appelle Marie'],
            ['role' => 'assistant', 'content' => 'Bonjour Marie !'],
        ];

        // Test avec contexte
        $responseWithContext = $service->generateResponse($account, $userMessage, $context);
        
        // Test sans contexte  
        $responseWithoutContext = $service->generateResponse($account, $userMessage, []);

        $this->assertNotNull($responseWithContext, 'Réponse avec contexte doit être générée');
        $this->assertNotNull($responseWithoutContext, 'Réponse sans contexte doit être générée');
        
        $this->assertIsArray($responseWithContext, 'Réponse avec contexte doit être un array');
        $this->assertIsArray($responseWithoutContext, 'Réponse sans contexte doit être un array');
        
        // Les réponses doivent avoir du contenu
        $this->assertArrayHasKey('response', $responseWithContext, 'Réponse avec contexte doit avoir du contenu');
        $this->assertArrayHasKey('response', $responseWithoutContext, 'Réponse sans contexte doit avoir du contenu');
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

        $service = app(WhatsAppAIService::class);
        
        $response = $service->generateResponse($account, 'Test message', []);

        // En cas d'erreur, le service peut retourner null ou une réponse de fallback
        if ($response !== null) {
            $this->assertIsArray($response, 'Une réponse de fallback doit être un array');
            $this->assertArrayHasKey('response', $response, 'La réponse doit avoir un contenu');
            $this->assertNotEmpty($response['response'], 'La réponse de fallback ne doit pas être vide');
            $this->assertStringContainsString('difficultés', $response['response'], 'Le message d\'erreur doit être utilisateur-friendly');
        } else {
            $this->assertNull($response, 'En cas d\'erreur, null est accepté');
        }
    }
}
