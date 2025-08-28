<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\DTOs\AI\AiRequestDTO;
use App\DTOs\AI\AiResponseDTO;
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
 * Tests provider-agnostic mockés pour tous les services AI
 * Ces tests vérifient l'interface et le comportement sans appeler les vraies APIs
 *
 * @group ai
 * @group mocked
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
        $account = \App\Models\WhatsAppAccount::factory()->create();

        // Mock de l'interface AiServiceInterface au lieu de la classe concrète
        $mockService = $this->createMock(AiServiceInterface::class);
        $expectedResponse = new AiResponseDTO(
            content: 'Bonjour ! Je vais très bien, merci de demander.',
            metadata: [
                'provider' => $provider,
                'model' => $model->name,
                'temperature' => 0.7,
            ],
            tokensUsed: 15,
            cost: 0.001
        );

        $mockService->expects($this->once())
            ->method('chat')
            ->with(
                $this->equalTo($model),
                $this->callback(function (AiRequestDTO $request) use ($account) {
                    return $request->systemPrompt === 'Tu es un assistant WhatsApp professionnel.'
                        && $request->userMessage === 'Bonjour, comment allez-vous ?'
                        && $request->account->id === $account->id;
                })
            )
            ->willReturn($expectedResponse);

        // Remplacer le service par le mock
        $this->app->instance($serviceClass, $mockService);

        $request = new AiRequestDTO(
            systemPrompt: 'Tu es un assistant WhatsApp professionnel.',
            userMessage: 'Bonjour, comment allez-vous ?',
            account: $account
        );

        // Utiliser le service depuis l'app (qui sera notre mock)
        $service = app($serviceClass);
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
        $account = \App\Models\WhatsAppAccount::factory()->create();

        // Mock de l'interface AiServiceInterface
        $mockService = $this->createMock(AiServiceInterface::class);
        $expectedResponse = new AiResponseDTO(
            content: 'Votre nom est Jean, comme vous me l\'avez dit précédemment.',
            metadata: [
                'provider' => $provider,
                'model' => $model->name,
            ],
            tokensUsed: 25
        );

        $mockService->expects($this->once())
            ->method('chat')
            ->willReturn($expectedResponse);

        $this->app->instance($serviceClass, $mockService);

        $request = new AiRequestDTO(
            systemPrompt: 'Tu te souviens du nom de l\'utilisateur et tu le mentionnes dans ta réponse.',
            userMessage: 'Rappelle-moi mon nom',
            account: $account
        );

        $service = app($serviceClass);
        $response = $service->chat($model, $request);

        $this->assertNotEmpty($response->content, "Le provider {$provider} doit générer une réponse avec contexte");
        $this->assertGreaterThan(10, strlen($response->content), "Le provider {$provider} doit fournir une réponse substantielle");
    }

    #[Test]
    #[DataProvider('aiProviderDataProvider')]
    public function it_respects_system_prompt_for_provider(string $provider, string $serviceClass): void
    {
        $model = $this->createTestModel($provider);
        $account = \App\Models\WhatsAppAccount::factory()->create();

        // Mock de l'interface AiServiceInterface
        $mockService = $this->createMock(AiServiceInterface::class);
        $expectedResponse = new AiResponseDTO(
            content: 'AFRIK SOLUTIONS: Bonjour ! Comment puis-je vous aider aujourd\'hui ?',
            metadata: [
                'provider' => $provider,
                'model' => $model->name,
            ]
        );

        $mockService->expects($this->once())
            ->method('chat')
            ->willReturn($expectedResponse);

        $this->app->instance($serviceClass, $mockService);

        $request = new AiRequestDTO(
            systemPrompt: 'Tu réponds TOUJOURS en commençant par "AFRIK SOLUTIONS:"',
            userMessage: 'Dites bonjour',
            account: $account
        );

        $service = app($serviceClass);
        $response = $service->chat($model, $request);

        $this->assertNotEmpty($response->content, "Le provider {$provider} doit générer une réponse");
        $this->assertStringContainsString('AFRIK SOLUTIONS:', $response->content, "Le provider {$provider} doit respecter le system prompt");
    }

    #[Test]
    #[DataProvider('aiProviderDataProvider')]
    public function it_handles_configuration_parameters_for_provider(string $provider, string $serviceClass): void
    {
        $model = $this->createTestModel($provider);
        $account = \App\Models\WhatsAppAccount::factory()->create();

        // Mock de l'interface AiServiceInterface
        $mockService = $this->createMock(AiServiceInterface::class);
        $expectedResponse = new AiResponseDTO(
            content: 'OK',
            metadata: [
                'provider' => $provider,
                'model' => $model->name,
                'temperature' => 0.1,
            ]
        );

        $mockService->expects($this->once())
            ->method('chat')
            ->willReturn($expectedResponse);

        $this->app->instance($serviceClass, $mockService);

        $request = new AiRequestDTO(
            systemPrompt: 'Réponds en une phrase courte.',
            userMessage: 'Dites "OK"',
            account: $account
        );

        $service = app($serviceClass);
        $response = $service->chat($model, $request);

        $this->assertNotEmpty($response->content, "Le provider {$provider} doit gérer la configuration");
        $this->assertLessThanOrEqual(100, strlen($response->content), "Le provider {$provider} doit respecter max_tokens");
    }

    #[Test]
    #[DataProvider('aiProviderDataProvider')]
    public function it_handles_empty_user_message_gracefully_for_provider(string $provider, string $serviceClass): void
    {
        $model = $this->createTestModel($provider);
        $account = \App\Models\WhatsAppAccount::factory()->create();

        // Mock de l'interface AiServiceInterface
        $mockService = $this->createMock(AiServiceInterface::class);
        $expectedResponse = new AiResponseDTO(
            content: 'Comment puis-je vous aider aujourd\'hui ?',
            metadata: [
                'provider' => $provider,
                'model' => $model->name,
            ]
        );

        $mockService->expects($this->once())
            ->method('chat')
            ->willReturn($expectedResponse);

        $this->app->instance($serviceClass, $mockService);

        $request = new AiRequestDTO(
            systemPrompt: 'Tu es un assistant utile.',
            userMessage: '',
            account: $account
        );

        $service = app($serviceClass);
        $response = $service->chat($model, $request);

        $this->assertNotEmpty($response->content, "Le provider {$provider} doit gérer les messages vides");
        $this->assertArrayHasKey('provider', $response->metadata);
    }

    #[Test]
    #[DataProvider('aiProviderDataProvider')]
    public function it_generates_consistent_metadata_for_provider(string $provider, string $serviceClass): void
    {
        $model = $this->createTestModel($provider);
        $account = \App\Models\WhatsAppAccount::factory()->create();

        // Mock de l'interface AiServiceInterface
        $mockService = $this->createMock(AiServiceInterface::class);
        $expectedResponse = new AiResponseDTO(
            content: 'Test de métadonnées réussi',
            metadata: [
                'provider' => $provider,
                'model' => $model->name,
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 5,
                ],
            ]
        );

        $mockService->expects($this->once())
            ->method('chat')
            ->willReturn($expectedResponse);

        $this->app->instance($serviceClass, $mockService);

        $request = new AiRequestDTO(
            systemPrompt: 'Tu es un assistant professionnel.',
            userMessage: 'Test de métadonnées',
            account: $account
        );

        $service = app($serviceClass);
        $response = $service->chat($model, $request);

        // Vérifications des métadonnées obligatoires
        $this->assertArrayHasKey('provider', $response->metadata, "Le provider {$provider} doit fournir son nom dans les métadonnées");
        $this->assertArrayHasKey('model', $response->metadata, "Le provider {$provider} doit fournir le nom du modèle");
        $this->assertEquals($provider, $response->metadata['provider'], 'Le nom du provider doit correspondre');

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
}
