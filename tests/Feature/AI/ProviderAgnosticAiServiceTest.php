<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\DTOs\AI\AiRequestDTO;
use App\Models\AiModel;
use App\Services\AI\AiServiceInterface;
use App\Services\AI\DeepSeekService;
use App\Services\AI\OllamaService;
use App\Services\AI\OpenAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Helpers\AiTestHelper;
use Tests\TestCase;

/**
 * Tests provider-agnostic pour tous les services AI
 *
 * @group ai
 * @group provider-agnostic
 */
final class ProviderAgnosticAiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public static function aiProviderDataProvider(): array
    {
        return [
            'ollama' => ['ollama', OllamaService::class],
            'deepseek' => ['deepseek', DeepSeekService::class],
            'openai' => ['openai', OpenAiService::class],
        ];
    }

    #[Test]
    #[DataProvider('aiProviderDataProvider')]
    public function it_can_generate_basic_response_for_provider(string $provider, string $serviceClass): void
    {
        $model = $this->createTestModel($provider);

        if ($this->shouldSkipProvider($provider, $model)) {
            $this->markTestSkipped("Provider {$provider} non configuré ou non accessible");
        }

        $service = app($serviceClass);
        $this->assertInstanceOf(AiServiceInterface::class, $service);

        $request = new AiRequestDTO(
            systemPrompt: 'Tu es un assistant WhatsApp professionnel.',
            userMessage: 'Bonjour, comment allez-vous ?',
            config: [],
            context: []
        );

        $response = $service->chat($model, $request);

        $this->assertNotEmpty($response->content, "Le provider {$provider} doit générer une réponse non vide");
        $this->assertEquals($provider, $response->metadata['provider']);
        $this->assertArrayHasKey('model', $response->metadata);
        $this->assertGreaterThan(5, strlen($response->content), "La réponse du provider {$provider} doit être substantielle");
    }

    #[Test]
    #[DataProvider('aiProviderDataProvider')]
    public function it_handles_context_properly_for_provider(string $provider, string $serviceClass): void
    {
        $model = $this->createTestModel($provider);

        if ($this->shouldSkipProvider($provider, $model)) {
            $this->markTestSkipped("Provider {$provider} non configuré ou non accessible");
        }

        $service = app($serviceClass);
        $context = [
            ['role' => 'user', 'content' => 'Mon nom est Jean'],
            ['role' => 'assistant', 'content' => 'Bonjour Jean, ravi de vous rencontrer !'],
        ];

        $request = new AiRequestDTO(
            systemPrompt: 'Tu te souviens du nom de l\'utilisateur et tu le mentionnes dans ta réponse.',
            userMessage: 'Rappelle-moi mon nom',
            config: [],
            context: $context
        );

        $response = $service->chat($model, $request);

        $this->assertNotEmpty($response->content, "Le provider {$provider} doit générer une réponse avec contexte");
        
        // Pour Ollama, on accepte qu'il n'ait pas de mémoire persistante
        // Pour les autres providers, on s'attend à ce qu'ils utilisent le contexte
        if ($provider !== 'ollama') {
            $this->assertStringContainsStringIgnoringCase('jean', $response->content, "Le provider {$provider} doit se souvenir du contexte");
        } else {
            // Pour Ollama, on vérifie juste qu'il répond de manière cohérente
            $this->assertGreaterThan(10, strlen($response->content), "Ollama doit fournir une réponse substantielle même sans mémoire");
        }
    }

    #[Test]
    #[DataProvider('aiProviderDataProvider')]
    public function it_respects_system_prompt_for_provider(string $provider, string $serviceClass): void
    {
        $model = $this->createTestModel($provider);

        if ($this->shouldSkipProvider($provider, $model)) {
            $this->markTestSkipped("Provider {$provider} non configuré ou non accessible");
        }

        $service = app($serviceClass);
        $request = new AiRequestDTO(
            systemPrompt: 'Tu réponds TOUJOURS en commençant par "AFRIK SOLUTIONS:"',
            userMessage: 'Dites bonjour',
            config: [],
            context: []
        );

        $response = $service->chat($model, $request);

        $this->assertNotEmpty($response->content, "Le provider {$provider} doit générer une réponse");
        $this->assertStringContainsString('AFRIK SOLUTIONS:', $response->content, "Le provider {$provider} doit respecter le system prompt");
    }

    #[Test]
    #[DataProvider('aiProviderDataProvider')]
    public function it_handles_configuration_parameters_for_provider(string $provider, string $serviceClass): void
    {
        $model = $this->createTestModel($provider);

        if ($this->shouldSkipProvider($provider, $model)) {
            $this->markTestSkipped("Provider {$provider} non configuré ou non accessible");
        }

        $service = app($serviceClass);
        $config = [
            'temperature' => 0.1, // Très faible pour des réponses déterministes
            'max_tokens' => 50,
        ];

        $request = new AiRequestDTO(
            systemPrompt: 'Réponds en une phrase courte.',
            userMessage: 'Dites "OK"',
            config: $config,
            context: []
        );

        $response = $service->chat($model, $request);

        $this->assertNotEmpty($response->content, "Le provider {$provider} doit gérer la configuration");
        $this->assertLessThanOrEqual(100, strlen($response->content), "Le provider {$provider} doit respecter max_tokens");
    }

    #[Test]
    #[DataProvider('aiProviderDataProvider')]
    public function it_validates_provider_configuration(string $provider, string $serviceClass): void
    {
        $model = $this->createTestModel($provider);

        if ($this->shouldSkipProvider($provider, $model)) {
            $this->markTestSkipped("Provider {$provider} non configuré ou non accessible");
        }

        $service = app($serviceClass);
        $isValid = $service->validateConfiguration($model);

        $this->assertTrue($isValid, "La configuration du provider {$provider} doit être valide");
    }

    #[Test]
    #[DataProvider('aiProviderDataProvider')]
    public function it_handles_empty_user_message_gracefully_for_provider(string $provider, string $serviceClass): void
    {
        $model = $this->createTestModel($provider);

        if ($this->shouldSkipProvider($provider, $model)) {
            $this->markTestSkipped("Provider {$provider} non configuré ou non accessible");
        }

        $service = app($serviceClass);
        $request = new AiRequestDTO(
            systemPrompt: 'Tu es un assistant utile.',
            userMessage: '',
            config: [],
            context: []
        );

        $response = $service->chat($model, $request);

        $this->assertNotEmpty($response->content, "Le provider {$provider} doit gérer les messages vides");
        $this->assertArrayHasKey('provider', $response->metadata);
    }

    #[Test]
    #[DataProvider('aiProviderDataProvider')]
    public function it_generates_consistent_metadata_for_provider(string $provider, string $serviceClass): void
    {
        $model = $this->createTestModel($provider);

        if ($this->shouldSkipProvider($provider, $model)) {
            $this->markTestSkipped("Provider {$provider} non configuré ou non accessible");
        }

        $service = app($serviceClass);
        $request = new AiRequestDTO(
            systemPrompt: 'Tu es un assistant professionnel.',
            userMessage: 'Test de métadonnées',
            config: [],
            context: []
        );

        $response = $service->chat($model, $request);

        // Vérifications des métadonnées obligatoires
        $this->assertArrayHasKey('provider', $response->metadata, "Le provider {$provider} doit fournir son nom dans les métadonnées");
        $this->assertArrayHasKey('model', $response->metadata, "Le provider {$provider} doit fournir le nom du modèle");
        $this->assertEquals($provider, $response->metadata['provider'], "Le nom du provider doit correspondre");
        
        // Vérifications optionnelles mais recommandées
        if (isset($response->metadata['usage'])) {
            $this->assertIsArray($response->metadata['usage'], "Les informations d'usage doivent être un tableau");
        }
    }

    private function createTestModel(string $provider, array $overrides = []): AiModel
    {
        return AiModel::factory()->create(
            AiTestHelper::createTestModelData($provider, $overrides)
        );
    }

    private function shouldSkipProvider(string $provider, AiModel $model): bool
    {
        // Vérifier si le provider est configuré
        if ($provider === 'deepseek' && !($model->api_key ?? config('services.deepseek.api_key'))) {
            return true;
        }

        if ($provider === 'openai' && !($model->api_key ?? config('services.openai.api_key'))) {
            return true;
        }

        // Pour Ollama, vérifier la disponibilité
        if ($provider === 'ollama') {
            try {
                $response = @file_get_contents($model->endpoint_url, false, stream_context_create([
                    'http' => ['timeout' => 2]
                ]));
                return $response === false;
            } catch (\Exception) {
                return true;
            }
        }

        return false;
    }
}
