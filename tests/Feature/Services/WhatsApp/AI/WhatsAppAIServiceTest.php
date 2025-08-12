<?php

declare(strict_types=1);

namespace Tests\Feature\Services\WhatsApp\AI;

use App\Models\AiModel;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\AI\WhatsAppAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WhatsAppAIServiceTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppAccount $account;
    private AiModel $ollamaModel;
    private WhatsAppAIService $aiService;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer le modèle Ollama de test
        $this->ollamaModel = AiModel::create([
            'name' => 'Test Ollama Gemma2',
            'provider' => 'ollama',
            'model_identifier' => 'gemma2:2b',
            'description' => 'Modèle de test Ollama',
            'endpoint_url' => 'http://209.126.83.125:11434',
            'requires_api_key' => false,
            'api_key' => null,
            'model_config' => json_encode([
                'temperature' => 0.7,
                'max_tokens' => 1000,
                'top_p' => 0.9,
            ]),
            'is_active' => true,
            'is_default' => true,
            'cost_per_1k_tokens' => 0.0,
            'max_context_length' => 8192,
        ]);

        // Créer un compte WhatsApp de test
        $user = User::factory()->create();
        $this->account = WhatsAppAccount::create([
            'user_id' => $user->id,
            'session_name' => 'test-phone-shop',
            'session_id' => 'test-session-123',
            'phone_number' => '+1234567890',
            'status' => 'connected',
            'agent_enabled' => true,
            'ai_model_id' => $this->ollamaModel->id,
            'agent_prompt' => 'Tu es un assistant de vente de téléphones. Tu es professionnel et chaleureux.',
            'contextual_information' => 'Nous vendons des téléphones. Nos produits: Google Pixel 6 en promotion à 100mille FCFA, Pixel 9 neuf scellé à 900mille FCFA, iPhone XR d\'occasion à 90mille FCFA. Garantie 1 an sur tous nos produits.',
            'response_time' => 'fast',
        ]);

        $this->aiService = app(WhatsAppAIService::class);
    }

    public function test_it_can_reply_normally(): void
    {
        $response = $this->aiService->generateResponse(
            $this->account,
            'Bonjour boss',
            []
        );

        $this->assertNotNull($response);
        $this->assertArrayHasKey('response', $response);
        $this->assertArrayHasKey('model', $response);
        $this->assertNotEmpty($response['response']);
        
        // Vérifier que la réponse contient un salut (bonjour, salut, etc.)
        $responseText = strtolower($response['response']);
        $this->assertTrue(
            str_contains($responseText, 'bonjour') || 
            str_contains($responseText, 'salut') || 
            str_contains($responseText, 'hello'),
            'La réponse devrait contenir un salut'
        );
    }

    public function test_it_can_keep_conversation_coherent(): void
    {
        // Premier message : salut
        $firstResponse = $this->aiService->generateResponse(
            $this->account,
            'Bonjour boss',
            []
        );

        $this->assertNotNull($firstResponse);
        // Vérifier que la réponse contient un salut (bonjour, salut, etc.)
        $responseText = strtolower($firstResponse['response']);
        $this->assertTrue(
            str_contains($responseText, 'bonjour') || 
            str_contains($responseText, 'salut') || 
            str_contains($responseText, 'hello'),
            'La réponse devrait contenir un salut'
        );

        // Construire le contexte avec la première réponse
        $context = [
            ['role' => 'user', 'content' => 'Bonjour boss'],
            ['role' => 'assistant', 'content' => $firstResponse['response']],
        ];

        // Deuxième message : demande des téléphones
        $secondResponse = $this->aiService->generateResponse(
            $this->account,
            'Quels sont les téléphones que vous vendez ?',
            $context
        );

        $this->assertNotNull($secondResponse);
        
        // Vérifier qu'il mentionne les téléphones du contexte (test principal)
        $this->assertStringContainsStringIgnoringCase('pixel', $secondResponse['response']);
        $this->assertStringContainsStringIgnoringCase('iphone', $secondResponse['response']);

        // Ajouter cette réponse au contexte
        $context[] = ['role' => 'user', 'content' => 'Quels sont les téléphones que vous vendez ?'];
        $context[] = ['role' => 'assistant', 'content' => $secondResponse['response']];

        // Troisième message : demande de prix spécifique
        $thirdResponse = $this->aiService->generateResponse(
            $this->account,
            'C\'est quoi le prix du Pixel 6 svp ?',
            $context
        );

        $this->assertNotNull($thirdResponse);
        
        // Vérifier qu'il mentionne le prix du contexte (100, mille, 000, etc.)
        $responseText = strtolower($thirdResponse['response']);
        $this->assertTrue(
            str_contains($responseText, '100') && 
            (str_contains($responseText, 'mille') || str_contains($responseText, '000')),
            'La réponse devrait mentionner le prix 100 avec mille ou 000'
        );
    }

    public function test_it_uses_contextual_information(): void
    {
        $response = $this->aiService->generateResponse(
            $this->account,
            'Avez-vous des téléphones neufs ?',
            []
        );

        $this->assertNotNull($response);
        
        // Doit mentionner les produits du contexte
        $responseText = strtolower($response['response']);
        $this->assertTrue(
            str_contains($responseText, 'pixel 9') || 
            str_contains($responseText, '900') ||
            str_contains($responseText, 'neuf'),
            'La réponse devrait mentionner les téléphones neufs du contexte'
        );
    }

    public function test_it_handles_missing_model_gracefully(): void
    {
        // Tester avec un compte sans modèle configuré
        $accountWithoutModel = WhatsAppAccount::create([
            'user_id' => $this->account->user_id,
            'session_name' => 'test-no-model',
            'session_id' => 'test-session-456',
            'phone_number' => '+1234567891',
            'status' => 'connected',
            'agent_enabled' => true,
            'ai_model_id' => null, // Pas de modèle
            'agent_prompt' => 'Tu es un assistant.',
            'response_time' => 'fast',
        ]);

        $response = $this->aiService->generateResponse(
            $accountWithoutModel,
            'Hello',
            []
        );

        $this->assertNotNull($response);
        $this->assertArrayHasKey('response', $response);
        // Devrait utiliser le modèle par défaut ou fallback
    }

    public function test_it_maintains_professional_tone(): void
    {
        $response = $this->aiService->generateResponse(
            $this->account,
            'Salut mec, tu vends quoi ?',
            []
        );

        $this->assertNotNull($response);
        
        // Même avec un message familier, la réponse doit rester professionnelle
        $responseText = strtolower($response['response']);
        $this->assertFalse(
            str_contains($responseText, 'mec') || 
            str_contains($responseText, 'salut mec'),
            'La réponse ne devrait pas copier le ton familier'
        );
    }
}
