<?php

declare(strict_types=1);

namespace Tests\Feature\Services\AI;

use App\DTOs\AI\AiRequestDTO;
use App\Models\AiModel;
use App\Services\AI\DeepSeekService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DeepSeekServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private DeepSeekService $deepseekService;
    private ?AiModel $deepseekModel;

    protected function setUp(): void
    {
        parent::setUp();

        // ⚠️ PROTECTION CONTRE LES APPELS IA NON DÉSIRÉS
        // Ces tests font de vrais appels API et consomment des tokens !
        // Ils ne s'exécutent que si explicitement demandé via une variable d'environnement
        if (! env('RUN_AI_INTEGRATION_TESTS', false)) {
            $this->markTestSkipped('Tests d\'intégration IA désactivés. Utilisez RUN_AI_INTEGRATION_TESTS=true pour les activer.');
        }

        $this->seed(\Database\Seeders\AiModelsSeeder::class);

        $this->deepseekService = app(DeepSeekService::class);
        $this->deepseekModel = AiModel::where('provider', 'deepseek')->first();

        if (! $this->deepseekModel || ! ($this->deepseekModel->api_key ?? config('services.deepseek.api_key'))) {
            $this->markTestSkipped('Le modèle DeepSeek ou la clé API n\'est pas configuré.');
        }
    }

    #[Test]
    public function it_can_test_connection_to_deepseek_api(): void
    {
        Log::info('🧪 Test connexion DeepSeek');

        $isConnected = $this->deepseekService->testConnection($this->deepseekModel);

        $this->assertTrue($isConnected, 'La connexion à DeepSeek doit réussir');
    }

    #[Test]
    public function it_can_validate_deepseek_configuration(): void
    {
        Log::info('🧪 Test validation configuration DeepSeek');

        $isValid = $this->deepseekService->validateConfiguration($this->deepseekModel);

        $this->assertTrue($isValid, 'La configuration DeepSeek doit être valide');
    }

    #[Test]
    public function it_can_generate_chat_response(): void
    {
        Log::info('🧪 Test génération réponse chat DeepSeek');

        // Créer un compte WhatsApp pour le test
        $account = \App\Models\WhatsAppAccount::factory()->create();

        $request = new AiRequestDTO(
            systemPrompt: 'Tu es un assistant WhatsApp professionnel et utile.',
            userMessage: 'Salut ! Comment ça va ?',
            account: $account,
            config: []
        );

        $response = $this->deepseekService->chat($this->deepseekModel, $request);

        $this->assertNotEmpty($response->content, 'La réponse ne doit pas être vide');
        $this->assertEquals('deepseek', $response->metadata['provider']);
        $this->assertNotEmpty($response->metadata['model'], 'Le nom du modèle doit être présent dans les métadonnées');

        Log::info('✅ Réponse générée par DeepSeek', [
            'content_length' => strlen($response->content),
            'metadata' => $response->metadata,
        ]);
    }

    #[Test]
    public function it_handles_errors_gracefully_with_invalid_key(): void
    {
        Log::info('🧪 Test gestion des erreurs DeepSeek');

        $this->expectException(\Exception::class);

        $invalidModel = clone $this->deepseekModel;
        $invalidModel->api_key = 'invalid-api-key';

        // Créer un compte WhatsApp pour le test
        $account = \App\Models\WhatsAppAccount::factory()->create();

        $request = new AiRequestDTO(
            systemPrompt: 'Test',
            userMessage: 'Test',
            account: $account,
            config: []
        );

        $this->deepseekService->chat($invalidModel, $request);
    }

    #[Test]
    public function it_respects_configuration_parameters(): void
    {
        Log::info('🧪 Test respect des paramètres de configuration DeepSeek');

        $customConfig = [
            'temperature' => 0.1,
            'max_tokens' => 5,
            'top_p' => 0.5,
        ];

        $this->deepseekModel->model_config = $customConfig;

        // Créer un compte WhatsApp pour le test
        $account = \App\Models\WhatsAppAccount::factory()->create();

        $request = new AiRequestDTO(
            systemPrompt: 'Réponds en un seul mot.',
            userMessage: 'Dis juste \'OK\'',
            account: $account,
            config: []
        );

        $response = $this->deepseekService->chat($this->deepseekModel, $request);

        $this->assertNotEmpty($response->content);
        // We check for a reasonable length as max_tokens is about tokens, not characters.
        $this->assertLessThan(50, strlen($response->content), 'La réponse doit être très courte avec max_tokens=5');

        Log::info('✅ Configuration DeepSeek respectée', [
            'response_length' => strlen($response->content),
            'config_used' => $customConfig,
        ]);
    }
}
