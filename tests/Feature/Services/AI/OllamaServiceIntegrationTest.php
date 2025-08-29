<?php

declare(strict_types=1);

namespace Tests\Feature\Services\AI;

use App\DTOs\AI\AiRequestDTO;
use App\Enums\ResponseTime;
use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use App\Services\AI\OllamaService;
use App\Services\WhatsApp\AI\AiResponseSimulator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class OllamaServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private OllamaService $ollamaService;
    private AiModel $ollamaModel;

    protected function setUp(): void
    {
        parent::setUp();

        // ⚠️ PROTECTION CONTRE LES APPELS IA NON DÉSIRÉS
        // Ces tests font de vrais appels API et consomment des tokens !
        // Ils ne s'exécutent que si explicitement demandé via une variable d'environnement
        if (! env('RUN_AI_INTEGRATION_TESTS', false)) {
            $this->markTestSkipped('Tests d\'intégration IA désactivés. Utilisez RUN_AI_INTEGRATION_TESTS=true pour les activer.');
        }

        $this->seed([
            \Database\Seeders\AiModelsSeeder::class,
        ]);

        $this->ollamaService = app(OllamaService::class);
        $this->ollamaModel = AiModel::where('provider', 'ollama')
            ->where('model_identifier', 'gemma2:2b')
            ->first();

        $this->assertNotNull($this->ollamaModel, 'Le modèle Ollama doit exister après le seeder');
    }

    #[Test]
    public function it_can_test_connection_to_ollama_server(): void
    {
        Log::info('🧪 Test connexion Ollama');

        $isConnected = $this->ollamaService->testConnection($this->ollamaModel);

        $this->assertTrue($isConnected, 'La connexion à Ollama doit réussir');
    }

    #[Test]
    public function it_can_validate_ollama_configuration(): void
    {
        Log::info('🧪 Test validation configuration Ollama');

        $isValid = $this->ollamaService->validateConfiguration($this->ollamaModel);

        $this->assertTrue($isValid, 'La configuration Ollama doit être valide');
    }

    #[Test]
    public function it_can_get_available_models(): void
    {
        Log::info('🧪 Test récupération modèles disponibles');

        $models = $this->ollamaService->getAvailableModels($this->ollamaModel);

        $this->assertIsArray($models);
        $this->assertNotEmpty($models, 'Des modèles doivent être disponibles');

        $modelNames = array_column($models, 'name');
        $this->assertContains('gemma2:2b', $modelNames, 'Le modèle gemma2:2b doit être disponible');

        Log::info('✅ Modèles trouvés', ['count' => count($models), 'models' => $modelNames]);
    }

    #[Test]
    public function it_can_validate_model_exists(): void
    {
        Log::info('🧪 Test validation existence du modèle');

        $exists = $this->ollamaService->validateModelExists($this->ollamaModel);

        $this->assertTrue($exists, 'Le modèle gemma2:2b doit exister sur le serveur Ollama');
    }

    #[Test]
    public function it_can_generate_chat_response(): void
    {
        Log::info('🧪 Test génération réponse chat');

        $request = new AiRequestDTO(
            systemPrompt: 'Tu es un assistant WhatsApp professionnel et utile.',
            userMessage: 'Salut ! Comment ça va ?',
            config: [],
            context: []
        );

        $response = $this->ollamaService->chat($this->ollamaModel, $request);

        $this->assertNotEmpty($response->content, 'La réponse ne doit pas être vide');
        $this->assertEquals('ollama', $response->metadata['provider']);
        $this->assertEquals('gemma2:2b', $response->metadata['model']);

        Log::info('✅ Réponse générée', [
            'content_length' => strlen($response->content),
            'metadata' => $response->metadata,
        ]);
    }

    #[Test]
    public function it_can_simulate_whatsapp_conversation(): void
    {
        Log::info('🧪 Test simulation conversation WhatsApp complète');

        try {
            // ✅ Stocker la valeur string, pas l'enum
            $account = WhatsAppAccount::factory()->create([
                'ai_model_id' => $this->ollamaModel->id,
                'agent_prompt' => 'Tu es un assistant commercial pour une boutique en ligne. Sois sympathique et professionnel.',
                'response_time' => 'random', // ✅ Valeur string
            ]);

            Log::info('✅ Account créé', ['account_id' => $account->id]);

            $simulator = app(AiResponseSimulator::class);
            Log::info('✅ Simulator instancié');

            // ✅ Créer l'enum explicitement dans le test
            $responseTime = ResponseTime::make('random');
            Log::info('✅ ResponseTime créé', ['value' => $responseTime->value]);

            $response = $simulator->simulate(
                model: $this->ollamaModel,
                prompt: $account->agent_prompt,
                userMessage: 'Bonjour, je cherche des informations sur vos produits',
                responseTime: $responseTime
            );

            $this->assertNotEmpty($response, 'La simulation doit retourner une réponse');
            $this->assertIsString($response);

            Log::info('✅ Simulation réussie', [
                'response_length' => strlen($response),
                'account_id' => $account->id,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur dans test simulation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    #[Test]
    public function it_handles_errors_gracefully(): void
    {
        Log::info('🧪 Test gestion des erreurs');

        try {
            // Créer un modèle avec un endpoint invalide
            $invalidModel = AiModel::factory()->create([
                'provider' => 'ollama',
                'model_identifier' => 'non-existent-model',
                'endpoint_url' => 'http://invalid-url:11434',
            ]);

            Log::info('✅ Modèle invalide créé', ['id' => $invalidModel->id]);

            $canConnect = $this->ollamaService->testConnection($invalidModel);
            $this->assertFalse($canConnect, 'La connexion doit échouer avec un endpoint invalide');

            // Test avec modèle inexistant
            $inexistentModel = clone $this->ollamaModel;
            $inexistentModel->model_identifier = 'model-that-does-not-exist';

            Log::info('✅ Test modèle inexistant', ['model' => $inexistentModel->model_identifier]);

            $exists = $this->ollamaService->validateModelExists($inexistentModel);
            $this->assertFalse($exists, 'Un modèle inexistant ne doit pas être validé');

            Log::info('✅ Tests d\'erreurs terminés');

        } catch (\Exception $e) {
            Log::error('❌ Erreur dans test gestion erreurs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    #[Test]
    public function it_respects_configuration_parameters(): void
    {
        Log::info('🧪 Test respect des paramètres de configuration');

        // Modifier la configuration du modèle
        $customConfig = [
            'temperature' => 0.1,
            'max_tokens' => 50,
            'top_p' => 0.5,
        ];

        $this->ollamaModel->update([
            'model_config' => json_encode($customConfig),
        ]);

        $request = new AiRequestDTO(
            systemPrompt: 'Réponds en un seul mot.',
            userMessage: 'Dis juste "OK"',
            config: [],
            context: []
        );

        $response = $this->ollamaService->chat($this->ollamaModel, $request);

        $this->assertNotEmpty($response->content);
        $this->assertLessThanOrEqual(100, strlen($response->content), 'La réponse doit être courte avec max_tokens=50');

        Log::info('✅ Configuration respectée', [
            'response_length' => strlen($response->content),
            'config_used' => $customConfig,
        ]);
    }

    #[Test]
    public function it_can_run_performance_test(): void
    {
        Log::info('🧪 Test de performance');

        $startTime = microtime(true);

        $request = new AiRequestDTO(
            systemPrompt: 'Tu es un assistant rapide.',
            userMessage: 'Salut !',
            config: [],
            context: []
        );

        $response = $this->ollamaService->chat($this->ollamaModel, $request);

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // en millisecondes

        $this->assertNotEmpty($response->content);
        $this->assertLessThan(30000, $duration, 'La réponse doit arriver en moins de 30 secondes');

        Log::info('✅ Performance mesurée', [
            'duration_ms' => round($duration, 2),
            'response_length' => strlen($response->content),
        ]);
    }
}
